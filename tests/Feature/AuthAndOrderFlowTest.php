<?php

namespace Tests\Feature;

use App\Models\User;
use App\Mail\EventAttendeeAnnouncement;
use App\Models\Coupon;
use App\Models\Event;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Models\TicketType;
use App\Services\QrCodeService;
use App\Services\SlipQrDecoderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
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

    public function test_scanner_can_lookup_ticket_without_changing_status(): void
    {
        $this->seed();
        $scanner = User::where('username', 'scanner')->firstOrFail();
        $ticket = $this->createApprovedTicket();

        $this->actingAs($scanner)
            ->postJson('/admin/scanner', ['code' => $ticket->uuid])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('ticket.uuid', $ticket->uuid)
            ->assertJsonPath('ticket.status', 'approved');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'approved',
        ]);
    }

    public function test_scanner_requires_current_status_for_check_in_and_check_out(): void
    {
        Http::fake();
        $this->seed();
        $scanner = User::where('username', 'scanner')->firstOrFail();
        $ticket = $this->createApprovedTicket();

        $this->actingAs($scanner)
            ->postJson('/admin/scanner', [
                'code' => $ticket->uuid,
                'action' => 'check_out',
            ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('ticket.status', 'approved');

        $this->actingAs($scanner)
            ->postJson('/admin/scanner', [
                'code' => $ticket->uuid,
                'action' => 'check_in',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('ticket.status', 'checked_in');

        $this->actingAs($scanner)
            ->postJson('/admin/scanner', [
                'code' => $ticket->uuid,
                'action' => 'check_in',
            ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('ticket.status', 'checked_in');

        $this->actingAs($scanner)
            ->postJson('/admin/scanner', [
                'code' => $ticket->uuid,
                'action' => 'check_out',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('ticket.status', 'checked_out');
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
            'terms_accepted' => '1',
            'slip' => $this->paymentSlip(),
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ]);

        $response->assertRedirect();
        $order = TicketOrder::firstOrFail();
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{3,4}-'.now()->format('md').'-001$/', $order->order_number);
    }

    public function test_checkout_decodes_uploaded_slip_qr_when_possible(): void
    {
        $result = app(SlipQrDecoderService::class)->parsePayloadForReview('https://bank.example/slip?amount=199.00&ref=ABC123&receiver=Event+Organizer&paid_at=2026-05-25T10:30:00');

        $this->assertSame('decoded', $result['slip_qr_status']);
        $this->assertSame(199.00, $result['slip_qr_amount_thb']);
        $this->assertSame('ABC123', $result['slip_qr_reference']);
        $this->assertSame('Event Organizer', $result['slip_qr_receiver']);
        $this->assertSame('url', $result['slip_qr_data']['format']);
    }

    public function test_slip_qr_parser_understands_thai_slip_verify_payload(): void
    {
        $result = app(SlipQrDecoderService::class)->parsePayloadForReview('00390006000001010300402180183451347458273955102TH910409D6');

        $this->assertSame('decoded', $result['slip_qr_status']);
        $this->assertSame('slip_verify', $result['slip_qr_data']['format']);
        $this->assertSame('004', $result['slip_qr_data']['slip_verify']['sending_bank']);
        $this->assertSame('018345134745827395', $result['slip_qr_reference']);
        $this->assertStringContainsString('Kasikornbank', $result['slip_qr_receiver']);
    }

    public function test_checkout_stores_no_qr_status_for_plain_slip_image(): void
    {
        Storage::fake('uploads');
        $this->seed();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->post('/orders', [
            'customer_name' => 'Slip Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'qr_payment',
            'terms_accepted' => '1',
            'slip' => UploadedFile::fake()->image('slip.jpg', 640, 640),
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'ticket_order_id' => TicketOrder::firstOrFail()->id,
            'slip_qr_status' => 'no_qr',
        ]);
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
            'terms_accepted' => '1',
            'slip' => $this->paymentSlip(),
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
            'terms_accepted' => '1',
            'slip' => $this->paymentSlip(),
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
        $event->update([
            'description' => '<p>Move with <strong>energy</strong> at this fitness event.</p>',
            'social_description' => null,
            'social_image_path' => 'social-share/demo.jpg',
        ]);

        $this->get('/events/'.$event->id)
            ->assertOk()
            ->assertSee('property="og:description"', false)
            ->assertSee('Move with energy at this fitness event.', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('social-share/demo.jpg', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"Event"', false)
            ->assertSee('"priceCurrency":"THB"', false)
            ->assertSee('https://www.google.com/maps/search', false)
            ->assertSee('href="'.$event->hosted_by_url.'"', false);
    }

    public function test_public_seo_files_expose_sitemap_and_hide_private_paths(): void
    {
        $this->seed();

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: '.route('seo.sitemap'), false)
            ->assertSee('Disallow: /admin/', false)
            ->assertSee('Disallow: /orders/', false)
            ->assertSee('Disallow: /tickets/', false);

        $event = Event::firstOrFail();

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<urlset', false)
            ->assertSee(route('events.show', $event), false)
            ->assertDontSee('/admin/', false)
            ->assertDontSee('/orders/', false);
    }

    public function test_private_pages_are_marked_noindex(): void
    {
        $this->seed();

        $this->get('/login')
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', false);

        $this->get('/orders/lookup')
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', false);
    }

    public function test_event_checkout_includes_bank_logo_metadata(): void
    {
        $this->seed();

        $this->get('/events/1')
            ->assertOk()
            ->assertSee('Krungthai Bank')
            ->assertSee('ธนาคารกรุงไทย')
            ->assertSee('ktb.svg', false)
            ->assertSee('terms_accepted', false)
            ->assertSee('Terms and Conditions / ข้อกำหนดและเงื่อนไข')
            ->assertSee(route('legal.terms'), false)
            ->assertDontSee('Privacy / ความเป็นส่วนตัว')
            ->assertDontSee('Refunds / คืนเงิน')
            ->assertDontSee(route('legal.privacy'), false);
    }

    public function test_public_legal_pages_render_from_footer_links(): void
    {
        foreach ([
            route('legal.terms', absolute: false) => 'Terms and Conditions',
            route('legal.privacy', absolute: false) => 'Privacy Policy',
            route('legal.refund', absolute: false) => 'Refund Policy',
            route('legal.event-admission', absolute: false) => 'Event Admission Policy',
            route('legal.cookies', absolute: false) => 'Cookie Policy',
        ] as $url => $heading) {
            $this->get($url)
                ->assertOk()
                ->assertSee($heading)
                ->assertSee('English')
                ->assertSee('ไทย')
                ->assertSee(route('legal.terms'), false)
                ->assertSee(route('legal.privacy'), false)
                ->assertSee(route('legal.refund'), false);
        }
    }

    public function test_event_checkout_can_show_countdown_and_full_ticket_price(): void
    {
        $this->seed();

        $event = Event::firstOrFail();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());
        $event->update(['show_countdown' => true]);
        $ticketType->update(['full_price_thb' => $ticketType->price_thb + 200]);

        $this->get('/events/'.$event->id)
            ->assertOk()
            ->assertSee('eventCountdown', false)
            ->assertSee('THB '.number_format($ticketType->full_price_thb));
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
            'terms_accepted' => '1',
            'slip' => $this->paymentSlip(),
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

    public function test_checkout_requires_terms_acceptance(): void
    {
        $this->seed();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->post('/orders', [
            'customer_name' => 'Terms Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'qr_payment',
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors('terms_accepted');
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
            'terms_accepted' => '1',
            'slip' => $this->paymentSlip(),
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
            'terms_accepted' => '1',
            'slip' => $this->paymentSlip(),
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $order = TicketOrder::with('tickets')->firstOrFail();
        $ticket = $order->tickets->first();

        $this->get('/tickets/'.$ticket->uuid.'?phone=0812345678')
            ->assertOk()
            ->assertSee('Ticket not active yet')
            ->assertDontSee('Save image / บันทึกรูป')
            ->assertDontSee('Save PDF / บันทึก PDF')
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

    public function test_promptpay_emvco_payload_can_include_decimal_amount_and_valid_crc(): void
    {
        $payload = app(QrCodeService::class)->promptPayPayload('081-234-5678', 100.50);
        $result = app(SlipQrDecoderService::class)->parsePayloadForReview($payload);
        $emvco = $result['slip_qr_data']['emv']['emvco'];

        $this->assertSame('decoded', $result['slip_qr_status']);
        $this->assertSame('dynamic', $emvco['initiation_method']);
        $this->assertSame('phone', $emvco['merchant_account_information']['promptpay_type']);
        $this->assertSame('0066812345678', $emvco['merchant_account_information']['promptpay_id']);
        $this->assertSame('THB', $emvco['currency']);
        $this->assertSame(100.50, $emvco['amount']);
        $this->assertSame('TH', $emvco['country_code']);
        $this->assertTrue($emvco['crc_checksum']['valid']);
    }

    public function test_promptpay_static_payload_has_no_amount(): void
    {
        $payload = app(QrCodeService::class)->promptPayPayload('081-234-5678');
        $result = app(SlipQrDecoderService::class)->parsePayloadForReview($payload);
        $emvco = $result['slip_qr_data']['emv']['emvco'];

        $this->assertSame('static', $emvco['initiation_method']);
        $this->assertNull($result['slip_qr_amount_thb']);
        $this->assertTrue($emvco['crc_checksum']['valid']);
    }

    public function test_ticket_qr_payload_is_only_ticket_uuid(): void
    {
        $ticket = new \App\Models\Ticket(['uuid' => 'ticket-uuid-123']);

        $this->assertSame('ticket-uuid-123', app(QrCodeService::class)->ticketPayload($ticket));
    }

    public function test_ticket_page_has_save_image_and_pdf_actions(): void
    {
        $this->seed();
        $ticket = $this->createApprovedTicket();

        $this->get('/tickets/'.$ticket->uuid.'?phone=0812345678')
            ->assertOk()
            ->assertSee('Save image / บันทึกรูป')
            ->assertSee('Save PDF / บันทึก PDF')
            ->assertSee('ticketExport', false)
            ->assertSee('/tickets/'.$ticket->uuid.'/qr', false);
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
                'terms_accepted' => '1',
                'slip' => $this->paymentSlip(),
                'items' => [
                    ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
                ],
            ])->assertSessionHasNoErrors()
            ->assertRedirect();

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
        $event = Event::firstOrFail();

        $this->actingAs($admin)
            ->put('/admin/users/'.$user->id, [
                'name' => $user->name,
                'username' => 'buyer',
                'phone' => '0899999999',
                'email' => $user->email,
                'role' => 'event_admin',
                'event_ids' => [$event->id],
            ])
            ->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'event_admin',
        ]);
        $this->assertDatabaseHas('event_user', [
            'event_id' => $event->id,
            'user_id' => $user->id,
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

    public function test_event_admin_cannot_access_unassigned_event(): void
    {
        $this->seed();

        $admin = User::where('username', 'eventadmin')->firstOrFail();
        $event = Event::create([
            'name' => 'Private Event',
            'description' => 'Only another team can manage this.',
            'venue' => 'Private Venue',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonth()->addHours(2),
            'is_published' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/events/'.$event->id.'/overview')
            ->assertForbidden();
    }

    public function test_event_description_allows_safe_html_and_removes_scripts(): void
    {
        $this->seed();

        $admin = User::where('username', 'admin')->firstOrFail();
        $event = Event::firstOrFail();

        $this->actingAs($admin)
            ->put('/admin/events/'.$event->id, [
                'name' => $event->name,
                'description' => '<h2>Show details</h2><p>Bring <strong>energy</strong>.</p><script>alert("bad")</script><a href="javascript:alert(1)">Bad link</a>',
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
            ])
            ->assertRedirect('/admin/events');

        $this->get('/events/'.$event->id)
            ->assertOk()
            ->assertSee('<strong>energy</strong>', false)
            ->assertSee('<h2>Show details</h2>', false)
            ->assertDontSee('alert("bad")', false)
            ->assertDontSee('javascript:', false);

        $this->assertStringNotContainsString('<script', $event->fresh()->description);
        $this->assertStringNotContainsString('javascript:', $event->fresh()->description);
    }

    public function test_profile_has_order_ticket_tabs_and_logout_button(): void
    {
        $this->seed();

        $user = User::factory()->create(['role' => 'customer', 'phone' => '0811111111']);
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->actingAs($user)
            ->post('/orders', [
                'customer_name' => 'Profile Buyer',
                'customer_phone' => '0811111111',
                'payment_method' => 'qr_payment',
                'terms_accepted' => '1',
                'slip' => $this->paymentSlip(),
                'items' => [
                    ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
                ],
            ])->assertRedirect();

        $this->actingAs($user)
            ->get('/')
            ->assertDontSee('Logout / ออกจากระบบ');

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Orders / ออเดอร์')
            ->assertSee('Tickets / ตั๋ว')
            ->assertSee('Logout / ออกจากระบบ');

        $this->actingAs($user)
            ->get('/profile?view=tickets')
            ->assertOk()
            ->assertSee($ticketType->name)
            ->assertSee('View order / ดูออเดอร์')
            ->assertSee($ticketType->event->name)
            ->assertSee('aspect-[4/5]', false);
    }

    public function test_profile_hides_past_event_orders_and_tickets(): void
    {
        $this->seed();

        $user = User::factory()->create(['role' => 'customer', 'phone' => '0811111111']);
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->actingAs($user)
            ->post('/orders', [
                'customer_name' => 'Profile Buyer',
                'customer_phone' => '0811111111',
                'payment_method' => 'qr_payment',
                'terms_accepted' => '1',
                'slip' => $this->paymentSlip(),
                'items' => [
                    ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
                ],
            ])->assertRedirect();

        $ticketType->event->update([
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('No orders yet');

        $this->actingAs($user)
            ->get('/profile?view=tickets')
            ->assertOk()
            ->assertSee('No tickets yet');
    }

    public function test_admin_lookup_can_search_by_order_number_only(): void
    {
        $this->seed();

        $admin = User::where('username', 'admin')->firstOrFail();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->post('/orders', [
            'customer_name' => 'Lookup Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'bank_transfer',
            'terms_accepted' => '1',
            'slip' => $this->paymentSlip(),
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $order = TicketOrder::firstOrFail();

        $this->actingAs($admin)
            ->get('/orders/lookup?order_number='.$order->order_number)
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Approve / อนุมัติ')
            ->assertDontSee('Delete / ลบ');
    }

    public function test_admin_can_recheck_existing_payment_slip_qr(): void
    {
        $this->seed();
        $admin = User::where('username', 'admin')->firstOrFail();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->post('/orders', [
            'customer_name' => 'Slip Recheck Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'qr_payment',
            'terms_accepted' => '1',
            'slip' => $this->paymentSlip(),
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $order = TicketOrder::with('payments')->firstOrFail();
        $order->update(['payment_slip_path' => 'payment-slips/recheck.png']);
        $order->payments()->firstOrFail()->update([
            'slip_path' => 'payment-slips/recheck.png',
            'slip_qr_status' => null,
        ]);

        $this->instance(SlipQrDecoderService::class, new class extends SlipQrDecoderService {
            public function decode(?string $slipPath): array
            {
                return $this->parsePayloadForReview('00390006000001010300402180183451347458273955102TH910409D6');
            }
        });

        $this->actingAs($admin)
            ->post('/admin/orders/'.$order->id.'/check-slip-qr')
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'ticket_order_id' => $order->id,
            'slip_qr_status' => 'decoded',
            'slip_qr_reference' => '018345134745827395',
        ]);
    }

    public function test_reused_slip_qr_is_marked_duplicate(): void
    {
        $this->seed();
        $admin = User::where('username', 'admin')->firstOrFail();
        $existingOrder = TicketOrder::query()->create([
            'order_number' => 'DUP-001',
            'customer_name' => 'First Buyer',
            'customer_phone' => '0811111111',
            'status' => 'pending',
            'subtotal_thb' => 100,
            'discount_thb' => 0,
            'total_thb' => 100,
            'payment_method' => 'qr_payment',
        ]);
        Payment::query()->create([
            'ticket_order_id' => $existingOrder->id,
            'method' => 'qr_payment',
            'amount_thb' => 100,
            'status' => 'submitted',
            'slip_qr_status' => 'decoded',
            'slip_qr_payload' => 'duplicate-payload',
            'slip_qr_reference' => 'DUP-REF-001',
        ]);

        $result = app(SlipQrDecoderService::class)->withDuplicateReview([
            'slip_qr_status' => 'decoded',
            'slip_qr_payload' => 'duplicate-payload',
            'slip_qr_reference' => 'DUP-REF-001',
            'slip_qr_data' => ['format' => 'emv'],
        ], new Payment);

        $this->assertSame('duplicate', $result['slip_qr_status']);
        $this->assertSame('DUP-001', $result['slip_qr_data']['duplicate']['order_number']);
        $this->assertSame('payload', $result['slip_qr_data']['duplicate']['matched_by']);

        $this->actingAs($admin)
            ->get('/admin/orders/'.$existingOrder->id)
            ->assertOk();
    }

    public function test_super_admin_can_delete_cancelled_order_with_tickets(): void
    {
        $this->seed();

        $admin = User::where('username', 'admin')->firstOrFail();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->post('/orders', [
            'customer_name' => 'Delete Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'bank_transfer',
            'terms_accepted' => '1',
            'slip' => $this->paymentSlip(),
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $order = TicketOrder::firstOrFail();
        $ticketId = $order->tickets()->firstOrFail()->id;

        $this->actingAs($admin)
            ->post('/admin/orders/'.$order->id.'/approve')
            ->assertRedirect();

        $this->actingAs($admin)
            ->post('/admin/orders/'.$order->id.'/cancel')
            ->assertRedirect();

        $this->actingAs($admin)
            ->delete('/admin/orders/'.$order->id)
            ->assertRedirect('/admin/orders');

        $this->assertDatabaseMissing('ticket_orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('tickets', ['id' => $ticketId]);
    }

    public function test_pending_order_cannot_be_deleted_before_cancel_or_refund(): void
    {
        $this->seed();

        $admin = User::where('username', 'admin')->firstOrFail();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->post('/orders', [
            'customer_name' => 'Keep Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'bank_transfer',
            'terms_accepted' => '1',
            'slip' => $this->paymentSlip(),
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $order = TicketOrder::firstOrFail();

        $this->actingAs($admin)
            ->delete('/admin/orders/'.$order->id)
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('ticket_orders', ['id' => $order->id]);
    }

    public function test_admin_can_update_ticket_holder_from_order_page(): void
    {
        $this->seed();

        $admin = User::where('username', 'admin')->firstOrFail();
        $ticket = $this->createApprovedTicket();

        $this->actingAs($admin)
            ->patch('/admin/events/'.$ticket->event_id.'/tickets/'.$ticket->id.'/holder', [
                'holder_name' => 'Updated Holder',
                'holder_phone' => '0899999999',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'holder_name' => 'Updated Holder',
            'holder_phone' => '0899999999',
        ]);
    }

    public function test_event_admin_bank_name_uses_thai_bank_dropdown(): void
    {
        $this->seed();

        $admin = User::where('username', 'eventadmin')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/events/1/edit')
            ->assertOk()
            ->assertSee('Select bank / เลือกธนาคาร')
            ->assertSee('Krungthai Bank')
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
                        'full_price_thb' => 799,
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
            'full_price_thb' => 799,
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
            'terms_accepted' => '1',
            'slip' => $this->paymentSlip(),
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

    public function test_order_approval_sends_line_message_to_line_customer(): void
    {
        $this->seed();
        config([
            'services.line.messaging_channel_access_token' => 'line-token',
            'services.line.messaging_channel_secret' => 'line-secret',
        ]);
        Http::fake([
            'api.line.me/v2/bot/message/push' => Http::response([], 200),
        ]);

        $admin = User::where('username', 'admin')->firstOrFail();
        $user = User::factory()->create([
            'role' => 'customer',
            'provider' => 'line',
            'provider_id' => 'Uline123',
            'phone' => '0812222222',
        ]);
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->actingAs($user)
            ->post('/orders', [
                'customer_name' => 'LINE Buyer',
                'customer_phone' => '0812222222',
                'payment_method' => 'qr_payment',
                'terms_accepted' => '1',
                'slip' => $this->paymentSlip(),
                'items' => [
                    ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
                ],
            ])->assertRedirect();

        $order = TicketOrder::firstOrFail();

        $this->actingAs($admin)
            ->post('/admin/orders/'.$order->id.'/approve')
            ->assertRedirect();

        Http::assertSent(fn ($request) => $request->url() === 'https://api.line.me/v2/bot/message/push'
            && $request['to'] === 'Uline123'
            && str_contains($request['messages'][0]['text'], $order->order_number));
    }

    public function test_user_can_save_web_push_subscription(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user)
            ->postJson('/push-subscriptions', [
                'endpoint' => 'https://push.example/subscription-1',
                'keys' => [
                    'p256dh' => 'public-key',
                    'auth' => 'auth-token',
                ],
                'contentEncoding' => 'aes128gcm',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_type' => User::class,
            'subscribable_id' => $user->id,
            'endpoint' => 'https://push.example/subscription-1',
        ]);
    }

    public function test_crm_can_upsert_and_lookup_customer(): void
    {
        config(['services.crm.webhook_token' => 'crm-secret']);

        $this->withToken('crm-secret')
            ->postJson('/crm/customers/upsert', [
                'customer' => [
                    'name' => 'CRM Buyer',
                    'phone' => '0813333333',
                    'email' => 'crm@example.com',
                    'line_user_id' => 'Ucrm123',
                    'line_friend_status' => 'followed',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('customer.line_user_id', 'Ucrm123');

        $this->withToken('crm-secret')
            ->getJson('/crm/customers/lookup?phone=0813333333')
            ->assertOk()
            ->assertJsonPath('customer.name', 'CRM Buyer')
            ->assertJsonPath('customer.line_user_id', 'Ucrm123');
    }

    public function test_checkout_pushes_customer_activity_to_crm_when_configured(): void
    {
        $this->seed();
        config([
            'services.crm.base_url' => 'https://crm.example.test/api',
            'services.crm.token' => 'crm-token',
        ]);
        Http::fake([
            'crm.example.test/api/customers/lookup*' => Http::response(['customer' => null], 404),
            'crm.example.test/api/customers/upsert' => Http::response(['ok' => true], 200),
            'crm.example.test/api/customer-activities' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'role' => 'customer',
            'phone' => '0814444444',
        ]);
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->actingAs($user)
            ->post('/orders', [
                'customer_name' => 'CRM Activity Buyer',
                'customer_phone' => '0814444444',
                'customer_email' => 'crm-activity@example.com',
                'payment_method' => 'qr_payment',
                'terms_accepted' => '1',
                'slip' => $this->paymentSlip(),
                'items' => [
                    ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
                ],
            ])
            ->assertRedirect();

        Http::assertSent(fn ($request) => $request->url() === 'https://crm.example.test/api/customer-activities'
            && $request['type'] === 'ticket_order_created'
            && $request['customer']['phone'] === '0814444444');
    }

    public function test_crm_can_get_order_detail(): void
    {
        $this->seed();
        config(['services.crm.webhook_token' => 'crm-secret']);
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->post('/orders', [
            'customer_name' => 'CRM Order Buyer',
            'customer_phone' => '0815555555',
            'payment_method' => 'qr_payment',
            'terms_accepted' => '1',
            'slip' => $this->paymentSlip(),
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $order = TicketOrder::firstOrFail();

        $this->withToken('crm-secret')
            ->getJson('/crm/orders/'.$order->id)
            ->assertOk()
            ->assertJsonPath('order.order_number', $order->order_number)
            ->assertJsonPath('customer.phone', '0815555555');
    }

    public function test_line_webhook_updates_friend_status(): void
    {
        config(['services.line.messaging_channel_secret' => 'secret']);
        $user = User::factory()->create([
            'role' => 'customer',
            'provider' => 'line',
            'provider_id' => 'Uline123',
        ]);

        $payload = json_encode([
            'events' => [
                [
                    'type' => 'follow',
                    'source' => ['userId' => 'Uline123'],
                ],
            ],
        ]);
        $signature = base64_encode(hash_hmac('sha256', $payload, 'secret', true));

        $this->withHeaders(['X-Line-Signature' => $signature])
            ->postJson('/line/webhook', json_decode($payload, true))
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'line_friend_status' => 'followed',
        ]);
    }

    public function test_event_admin_can_send_line_and_web_push_message_to_attendees(): void
    {
        $this->seed();
        Notification::fake();
        config([
            'services.line.messaging_channel_access_token' => 'line-token',
            'services.line.messaging_channel_secret' => 'line-secret',
            'webpush.vapid.public_key' => 'test-public-key',
            'webpush.vapid.private_key' => 'test-private-key',
        ]);
        Http::fake([
            'api.line.me/v2/bot/message/push' => Http::response([], 200),
        ]);

        $admin = User::where('username', 'admin')->firstOrFail();
        $user = User::factory()->create([
            'role' => 'customer',
            'provider' => 'line',
            'provider_id' => 'Uline123',
            'phone' => '0812222222',
        ]);
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        $this->actingAs($user)
            ->post('/orders', [
                'customer_name' => 'Notify Buyer',
                'customer_phone' => '0812222222',
                'payment_method' => 'qr_payment',
                'terms_accepted' => '1',
                'slip' => $this->paymentSlip(),
                'items' => [
                    ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
                ],
            ])->assertRedirect();

        $order = TicketOrder::firstOrFail();
        $order->update(['status' => 'approved']);
        $user->updatePushSubscription('https://push.example/subscription-1', 'public-key', 'auth-token', 'aes128gcm');

        $this->actingAs($admin)
            ->post('/admin/events/'.$ticketType->event_id.'/message-attendees', [
                'subject' => 'Event reminder',
                'message' => 'Doors open soon.',
                'audience' => 'approved',
                'channels' => ['line', 'web_push'],
            ])
            ->assertRedirect();

        Http::assertSent(fn ($request) => $request->url() === 'https://api.line.me/v2/bot/message/push'
            && str_contains($request['messages'][0]['text'], 'Doors open soon.'));

        Notification::assertSentTo($user, \App\Notifications\CustomerWebPushNotification::class);
    }

    public function test_notification_ui_is_hidden_when_channels_are_not_configured(): void
    {
        $this->seed();
        config([
            'services.line.messaging_channel_access_token' => null,
            'services.line.messaging_channel_secret' => null,
            'services.line.official_account_url' => null,
            'webpush.vapid.public_key' => null,
            'webpush.vapid.private_key' => null,
        ]);

        $admin = User::where('username', 'admin')->firstOrFail();
        $event = Event::firstOrFail();

        $this->actingAs($admin)
            ->get('/profile')
            ->assertOk()
            ->assertDontSee('Enable Web Push / เปิด Web Push')
            ->assertDontSee('LINE updates / แจ้งเตือนผ่าน LINE');

        $this->actingAs($admin)
            ->get('/admin/events/'.$event->id.'/overview')
            ->assertOk()
            ->assertDontSee('Attendee notifications / ส่งการแจ้งเตือนถึงผู้เข้าร่วม')
            ->assertDontSee('LINE Messaging API')
            ->assertDontSee('Web Push');
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
            'terms_accepted' => '1',
            'coupon_code' => 'ITEM100',
            'slip' => $this->paymentSlip(),
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

    public function test_buy_x_get_y_promotion_discounts_free_ticket(): void
    {
        $this->seed();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        Promotion::create([
            'event_id' => $ticketType->event_id,
            'ticket_type_id' => $ticketType->id,
            'name' => 'Buy 2 Get 1',
            'promotion_type' => 'buy_x_get_y',
            'buy_quantity' => 2,
            'get_quantity' => 1,
            'combines_with_coupons' => true,
            'is_active' => true,
        ]);

        $this->post('/orders', [
            'customer_name' => 'Demo Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'bank_transfer',
            'terms_accepted' => '1',
            'slip' => $this->paymentSlip(),
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 3],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('ticket_orders', [
            'subtotal_thb' => $ticketType->price_thb * 3,
            'discount_thb' => $ticketType->price_thb,
            'total_thb' => $ticketType->price_thb * 2,
        ]);
    }

    public function test_promotion_can_skip_coupon_stacking(): void
    {
        $this->seed();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        Coupon::create([
            'event_id' => $ticketType->event_id,
            'ticket_type_id' => $ticketType->id,
            'code' => 'SAVE100',
            'discount_type' => 'fixed',
            'discount_scope' => 'order',
            'discount_value' => 100,
            'is_active' => true,
        ]);

        Promotion::create([
            'event_id' => $ticketType->event_id,
            'ticket_type_id' => $ticketType->id,
            'name' => 'Buy 2 Get 1',
            'promotion_type' => 'buy_x_get_y',
            'buy_quantity' => 2,
            'get_quantity' => 1,
            'combines_with_coupons' => false,
            'is_active' => true,
        ]);

        $this->post('/orders', [
            'customer_name' => 'Demo Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'bank_transfer',
            'terms_accepted' => '1',
            'coupon_code' => 'SAVE100',
            'slip' => $this->paymentSlip(),
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 3],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('ticket_orders', [
            'subtotal_thb' => $ticketType->price_thb * 3,
            'discount_thb' => 100,
            'total_thb' => ($ticketType->price_thb * 3) - 100,
        ]);
    }

    public function test_cash_payment_bypasses_slip_but_still_waits_for_admin_approval(): void
    {
        $this->seed();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());
        $ticketType->event->update(['payment_methods' => ['cash']]);

        $this->post('/orders', [
            'customer_name' => 'Cash Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'cash',
            'terms_accepted' => '1',
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $order = TicketOrder::with('tickets')->firstOrFail();

        $this->assertSame('pending', $order->status);
        $this->assertSame('pending', $order->tickets->first()->status);
        $this->assertDatabaseHas('payments', [
            'ticket_order_id' => $order->id,
            'method' => 'cash',
            'status' => 'cash_pending',
        ]);
    }

    public function test_free_ticket_auto_approves_without_payment_slip(): void
    {
        $this->seed();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());
        $ticketType->update(['price_thb' => 0]);

        $this->post('/orders', [
            'customer_name' => 'Free Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'qr_payment',
            'terms_accepted' => '1',
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $order = TicketOrder::with('tickets')->firstOrFail();

        $this->assertSame('approved', $order->status);
        $this->assertSame(0, $order->total_thb);
        $this->assertNotNull($order->approved_at);
        $this->assertSame('approved', $order->tickets->first()->status);
        $this->assertDatabaseHas('payments', [
            'ticket_order_id' => $order->id,
            'status' => 'waived',
        ]);
    }

    public function test_checkout_accepts_dynamic_event_payment_account(): void
    {
        $this->seed();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());
        $ticketType->event->update([
            'payment_accounts' => [
                [
                    'key' => 'promptpay-main',
                    'method' => 'qr_payment',
                    'label' => 'Main PromptPay',
                    'account_name' => 'Main Account',
                    'account_number' => '0812345678',
                    'instructions' => 'Pay this account.',
                    'is_active' => true,
                ],
            ],
        ]);

        $this->post('/orders', [
            'customer_name' => 'Dynamic Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'qr_payment',
            'payment_account_key' => 'promptpay-main',
            'terms_accepted' => '1',
            'slip' => $this->paymentSlip(),
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('ticket_orders', [
            'customer_name' => 'Dynamic Buyer',
            'payment_method' => 'qr_payment',
        ]);
        $this->assertDatabaseHas('payments', [
            'method' => 'qr_payment',
            'status' => 'submitted',
        ]);
    }

    public function test_hidden_coupon_is_not_suggested_but_can_still_be_applied(): void
    {
        $this->seed();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        Coupon::create([
            'event_id' => $ticketType->event_id,
            'ticket_type_id' => $ticketType->id,
            'code' => 'QUIET100',
            'discount_type' => 'fixed',
            'discount_scope' => 'order',
            'discount_value' => 100,
            'show_on_checkout' => false,
            'is_active' => true,
        ]);

        $this->get('/events/'.$ticketType->event_id)
            ->assertOk()
            ->assertSee('QUIET100');

        $this->post('/orders', [
            'customer_name' => 'Coupon Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'bank_transfer',
            'terms_accepted' => '1',
            'coupon_code' => 'QUIET100',
            'slip' => $this->paymentSlip(),
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('ticket_orders', [
            'discount_thb' => 100,
            'total_thb' => $ticketType->price_thb - 100,
        ]);
    }

    public function test_hidden_promotion_is_not_suggested_but_can_still_be_applied(): void
    {
        $this->seed();
        $ticketType = TicketType::query()
            ->get()
            ->first(fn (TicketType $ticketType) => $ticketType->isOnSale());

        Promotion::create([
            'event_id' => $ticketType->event_id,
            'ticket_type_id' => $ticketType->id,
            'name' => 'Secret bulk discount',
            'promotion_type' => 'fixed',
            'discount_scope' => 'order',
            'min_quantity' => 2,
            'discount_value' => 100,
            'show_on_event_page' => false,
            'is_active' => true,
        ]);

        $this->get('/events/'.$ticketType->event_id)
            ->assertOk()
            ->assertSee('Secret bulk discount');

        $this->post('/orders', [
            'customer_name' => 'Promotion Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'bank_transfer',
            'terms_accepted' => '1',
            'slip' => $this->paymentSlip(),
            'items' => [
                ['ticket_type_id' => $ticketType->id, 'quantity' => 2],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('ticket_orders', [
            'subtotal_thb' => $ticketType->price_thb * 2,
            'discount_thb' => 100,
            'total_thb' => ($ticketType->price_thb * 2) - 100,
        ]);
    }

    public function test_markdown_event_description_renders_safe_html(): void
    {
        $this->seed();
        $event = Event::firstOrFail();
        $event->update([
            'description_format' => 'markdown',
            'description' => "## Race pack\n\n- Shirt\n- Bib\n\n[Map](https://example.com)\n\n<script>alert('x')</script>",
        ]);

        $this->get('/events/'.$event->id)
            ->assertOk()
            ->assertSee('<h2>Race pack</h2>', false)
            ->assertSee('<li>Shirt</li>', false)
            ->assertSee('href="https://example.com"', false)
            ->assertDontSee('<script>', false);
    }

    private function createApprovedTicket(): Ticket
    {
        $event = Event::firstOrFail();
        $ticketType = $event->ticketTypes()->firstOrFail();
        $order = TicketOrder::create([
            'order_number' => 'SCAN-TEST-001',
            'customer_name' => 'Scan Tester',
            'customer_phone' => '0812345678',
            'status' => 'approved',
            'subtotal_thb' => $ticketType->price_thb,
            'discount_thb' => 0,
            'total_thb' => $ticketType->price_thb,
            'payment_method' => 'qr_payment',
            'approved_at' => now(),
        ]);
        $item = OrderItem::create([
            'ticket_order_id' => $order->id,
            'event_id' => $event->id,
            'ticket_type_id' => $ticketType->id,
            'quantity' => 1,
            'unit_price_thb' => $ticketType->price_thb,
            'line_total_thb' => $ticketType->price_thb,
        ]);

        return Ticket::create([
            'uuid' => (string) Str::uuid(),
            'ticket_order_id' => $order->id,
            'order_item_id' => $item->id,
            'event_id' => $event->id,
            'ticket_type_id' => $ticketType->id,
            'holder_name' => 'Scan Tester',
            'holder_phone' => '0812345678',
            'status' => 'approved',
        ]);
    }

    private function paymentSlip(): UploadedFile
    {
        return UploadedFile::fake()->image('payment-slip.jpg', 640, 640);
    }
}
