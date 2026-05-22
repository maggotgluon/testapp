<?php

namespace Tests\Feature;

use App\Models\User;
use App\Mail\EventAttendeeAnnouncement;
use App\Models\Coupon;
use App\Models\Event;
use App\Models\TicketOrder;
use App\Models\TicketType;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthAndOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_login_rejects_admin_accounts(): void
    {
        $this->seed();

        $this->post('/login', [
            'name' => 'Super Admin',
            'phone' => '0809166690',
            'provider' => 'guest',
        ])->assertSessionHasErrors('phone');
    }

    public function test_home_redirects_to_the_event_when_only_one_event_is_visible(): void
    {
        $this->seed();

        $event = Event::query()->visible()->firstOrFail();

        $this->get('/')
            ->assertRedirect(route('events.show', $event, false));
    }

    public function test_admin_login_accepts_username_and_phone(): void
    {
        $this->seed();

        $this->post('/admin/login', [
            'username' => 'admin',
            'phone' => '0809166690',
        ])->assertRedirect('/admin');
    }

    public function test_local_admin_login_can_use_role_dropdown(): void
    {
        $this->seed();
        $this->app['env'] = 'local';

        $this->withSession(['_token' => 'test-token'])->post('/admin/login', [
            '_token' => 'test-token',
            'role' => 'gate_scanner',
        ])->assertRedirect('/admin');
    }

    public function test_missing_social_credentials_show_a_clear_message(): void
    {
        config([
            'services.line.client_id' => null,
            'services.line.client_secret' => null,
        ]);

        $this->get('/auth/line')
            ->assertRedirect('/login')
            ->assertSessionHas('status');
    }

    public function test_line_liff_login_verifies_token_and_logs_customer_in(): void
    {
        config([
            'services.line.client_id' => '1234567890',
            'services.line.liff_id' => '1234567890-demo',
        ]);

        Http::fake([
            'api.line.me/oauth2/v2.1/verify' => Http::response([
                'sub' => 'U123456789',
                'name' => 'LINE Buyer',
                'picture' => 'https://example.com/avatar.jpg',
                'email' => 'buyer@example.com',
            ]),
        ]);

        $this->postJson('/auth/line/liff', [
            'id_token' => 'valid-line-id-token',
            'profile' => [
                'userId' => 'U123456789',
                'displayName' => 'LINE Buyer',
                'pictureUrl' => 'https://example.com/avatar.jpg',
            ],
            'redirect' => '/events/1',
        ])->assertOk()
            ->assertJsonPath('redirect', '/events/1');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'LINE Buyer',
            'email' => 'buyer@example.com',
            'provider' => 'line',
            'provider_id' => 'U123456789',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);
    }

    public function test_login_page_does_not_show_manual_liff_button(): void
    {
        config([
            'services.line.client_id' => 'line-client',
            'services.line.client_secret' => 'line-secret',
            'services.line.liff_id' => 'line-client-demo',
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertSee('LINE')
            ->assertDontSee('LINE LIFF');
    }

    public function test_event_checkout_guest_line_button_uses_oauth_flow(): void
    {
        $this->seed();
        config([
            'services.line.client_id' => 'line-client',
            'services.line.client_secret' => 'line-secret',
            'services.line.liff_id' => 'line-client-demo',
        ]);

        $this->get('/events/1')
            ->assertOk()
            ->assertSee('Login with LINE')
            ->assertSee('/auth/line?redirect=%2Fevents%2F1', false)
            ->assertDontSee('LINE LIFF');
    }

    public function test_order_number_is_human_readable(): void
    {
        $this->seed();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $response = $this->post('/orders', [
            'customer_name' => 'Demo Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'bank_transfer',
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ]);

        $response->assertRedirect();
        $order = TicketOrder::firstOrFail();
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{3,4}-'.now()->format('md').'-001$/', $order->order_number);
    }

    public function test_guest_order_redirect_includes_lookup_phone_and_confirmation_guidance(): void
    {
        $this->seed();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->post('/orders', [
            'customer_name' => 'Guest Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'qr_payment',
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $order = TicketOrder::firstOrFail();

        $this->get('/orders/'.$order->id.'?phone=0812345678')
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Save this order to your account')
            ->assertSee('claim_order='.$order->id, false);
    }

    public function test_guest_order_can_be_attached_after_customer_login(): void
    {
        $this->seed();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->post('/orders', [
            'customer_name' => 'Guest Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'qr_payment',
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $order = TicketOrder::firstOrFail();

        $this->get('/login?'.http_build_query([
            'redirect' => '/orders/'.$order->id.'?phone=0812345678',
            'claim_order' => $order->id,
            'phone' => '0812345678',
        ]))->assertOk();

        $this->post('/login', [
            'name' => 'Guest Buyer',
            'phone' => '0812345678',
            'provider' => 'guest',
        ])->assertRedirect('/orders/'.$order->id.'?phone=0812345678');

        $user = User::where('phone', '0812345678')->firstOrFail();

        $this->assertDatabaseHas('ticket_orders', [
            'id' => $order->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('tickets', [
            'ticket_order_id' => $order->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_event_page_renders_social_meta_and_optional_links(): void
    {
        $this->seed();

        $event = Event::firstOrFail();
        $event->update(['social_image_path' => 'social-share/demo.jpg']);

        $this->get('/events/'.$event->id)
            ->assertOk()
            ->assertSee('property="og:description"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('social-share/demo.jpg', false)
            ->assertSee('https://www.google.com/maps/search', false)
            ->assertSee('href="'.$event->hosted_by_url.'"', false);
    }

    public function test_event_checkout_includes_bank_logo_metadata(): void
    {
        $this->seed();

        $this->get('/events/1')
            ->assertOk()
            ->assertSee('Krungthai Bank')
            ->assertSee('ธนาคารกรุงไทย')
            ->assertSee('ktb.svg', false);
    }

    public function test_checkout_can_store_ticket_holder_names(): void
    {
        $this->seed();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->post('/orders', [
            'customer_name' => 'Main Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'qr_payment',
            'items' => [
                [
                    'ticket_type_id' => $ticketType->id,
                    'quantity' => 2,
                    'holders' => ['Guest One', 'Guest Two'],
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('tickets', ['holder_name' => 'Guest One']);
        $this->assertDatabaseHas('tickets', ['holder_name' => 'Guest Two']);
    }

    public function test_checkout_uses_buyer_name_when_ticket_holder_is_blank(): void
    {
        $this->seed();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->post('/orders', [
            'customer_name' => 'Main Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'qr_payment',
            'items' => [
                [
                    'ticket_type_id' => $ticketType->id,
                    'quantity' => 1,
                    'holders' => [''],
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('tickets', ['holder_name' => 'Main Buyer']);
    }

    public function test_pending_ticket_hides_qr_until_payment_is_approved(): void
    {
        $this->seed();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->post('/orders', [
            'customer_name' => 'Pending Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'qr_payment',
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $order = TicketOrder::with('tickets')->firstOrFail();
        $ticket = $order->tickets->first();

        $this->get('/tickets/'.$ticket->uuid.'?phone=0812345678')
            ->assertOk()
            ->assertSee('Ticket not active yet')
            ->assertDontSee('/tickets/'.$ticket->uuid.'/qr', false);

        $order->update(['status' => 'approved']);
        $ticket->update(['status' => 'approved']);

        $this->get('/tickets/'.$ticket->uuid.'?phone=0812345678')
            ->assertOk()
            ->assertSee('/tickets/'.$ticket->uuid.'/qr', false);
    }

    public function test_promptpay_payment_payload_matches_emv_format(): void
    {
        $payload = app(QrCodeService::class)->promptPayPayload('081-234-5678', 123);

        $this->assertSame('00020101021229370016A000000677010111011300668123456785802TH53037645406123.006304B598', $payload);
    }

    public function test_ticket_qr_payload_is_only_ticket_uuid(): void
    {
        $ticket = new \App\Models\Ticket(['uuid' => 'ticket-uuid-123']);

        $this->assertSame('ticket-uuid-123', app(QrCodeService::class)->ticketPayload($ticket));
    }

    public function test_checkout_updates_logged_in_customer_profile(): void
    {
        $this->seed();
        $user = User::factory()->create([
            'name' => 'Old Name',
            'phone' => null,
            'email' => null,
            'role' => 'customer',
        ]);
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->actingAs($user)
            ->post('/orders', [
                'customer_name' => 'Updated Buyer',
                'customer_phone' => '0811111111',
                'customer_email' => 'updated@example.com',
                'payment_method' => 'bank_transfer',
                'items' => [
                    ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
                ],
            ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Buyer',
            'phone' => '0811111111',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_super_admin_can_manage_user_roles(): void
    {
        $this->seed();

        $admin = User::where('username', 'admin')->firstOrFail();
        $user = User::factory()->create(['role' => 'customer', 'username' => 'buyer']);

        $this->actingAs($admin)
            ->put('/admin/users/'.$user->id, [
                'name' => $user->name,
                'username' => 'buyer',
                'phone' => '0899999999',
                'email' => $user->email,
                'role' => 'event_admin',
            ])
            ->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'event_admin',
        ]);
    }

    public function test_event_admin_can_view_event_operations_overview(): void
    {
        $this->seed();

        $admin = User::where('username', 'eventadmin')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/events/1/overview')
            ->assertOk()
            ->assertSee('Event operations')
            ->assertSee('Ticket/check-in status')
            ->assertSee('Orders for this event');
    }

    public function test_event_admin_bank_name_uses_thai_bank_dropdown(): void
    {
        $this->seed();

        $admin = User::where('username', 'eventadmin')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/events/1/edit')
            ->assertOk()
            ->assertSee('Select bank / เลือกธนาคาร')
            ->assertSee('Krungthai Bank / ธนาคารกรุงไทย')
            ->assertSee('ktb.svg', false);
    }

    public function test_event_admin_can_add_and_remove_ticket_types(): void
    {
        $this->seed();

        $admin = User::where('username', 'eventadmin')->firstOrFail();
        $event = Event::firstOrFail();
        $ticketType = $event->ticketTypes()->firstOrFail();

        $this->actingAs($admin)
            ->put('/admin/events/'.$event->id, [
                'name' => $event->name,
                'description' => $event->description,
                'social_description' => $event->social_description,
                'venue' => $event->venue,
                'location' => $event->location,
                'location_url' => $event->location_url,
                'hosted_by' => $event->hosted_by,
                'hosted_by_url' => $event->hosted_by_url,
                'starts_at' => $event->starts_at->format('Y-m-d H:i:s'),
                'ends_at' => $event->ends_at->format('Y-m-d H:i:s'),
                'is_published' => '1',
                'bank_name' => $event->bank_name,
                'bank_account_name' => $event->bank_account_name,
                'bank_account_number' => $event->bank_account_number,
                'qr_payment_account_name' => $event->qr_payment_account_name,
                'qr_payment_account' => $event->qr_payment_account,
                'payment_instructions' => $event->payment_instructions,
                'inactive_ticket_type_ids' => [$ticketType->id],
                'tickets' => [
                    [
                        'name' => 'Door ticket',
                        'description' => 'At-door admission',
                        'price_thb' => 599,
                        'capacity' => 25,
                        'status' => 'active',
                    ],
                ],
            ])
            ->assertRedirect('/admin/events');

        $this->assertDatabaseHas('ticket_types', [
            'id' => $ticketType->id,
            'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('ticket_types', [
            'event_id' => $event->id,
            'name' => 'Door ticket',
            'status' => 'active',
        ]);
    }

    public function test_event_admin_can_email_approved_attendees_with_email(): void
    {
        $this->seed();
        Mail::fake();

        $admin = User::where('username', 'eventadmin')->firstOrFail();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->post('/orders', [
            'customer_name' => 'Email Buyer',
            'customer_phone' => '0812345678',
            'customer_email' => 'attendee@example.com',
            'payment_method' => 'bank_transfer',
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        TicketOrder::firstOrFail()->update(['status' => 'approved']);

        $this->actingAs($admin)
            ->post('/admin/events/'.$ticketType->event_id.'/email-attendees', [
                'subject' => 'Event update',
                'message' => 'Doors open at 6 PM.',
                'audience' => 'approved',
            ])
            ->assertRedirect();

        Mail::assertSent(EventAttendeeAnnouncement::class, function (EventAttendeeAnnouncement $mail) use ($ticketType) {
            return $mail->hasTo('attendee@example.com')
                && $mail->event->id === $ticketType->event_id
                && $mail->subjectLine === 'Event update';
        });
    }

    public function test_coupon_can_discount_each_ticket_item(): void
    {
        $this->seed();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        Coupon::create([
            'event_id' => $ticketType->event_id,
            'ticket_type_id' => $ticketType->id,
            'code' => 'ITEM100',
            'discount_type' => 'fixed',
            'discount_scope' => 'item',
            'discount_value' => 100,
            'is_active' => true,
        ]);

        $this->post('/orders', [
            'customer_name' => 'Demo Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'bank_transfer',
            'coupon_code' => 'ITEM100',
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('ticket_orders', [
            'subtotal_thb' => $ticketType->price_thb * 2,
            'discount_thb' => 200,
            'total_thb' => ($ticketType->price_thb * 2) - 200,
        ]);
    }
}
