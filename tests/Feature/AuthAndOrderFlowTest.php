<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Coupon;
use App\Models\TicketOrder;
use App\Models\TicketType;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
        ]);
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

    public function test_promptpay_payment_payload_matches_emv_format(): void
    {
        $payload = app(QrCodeService::class)->promptPayPayload('081-234-5678', 123);

        $this->assertSame('00020101021229370016A000000677010111011300668123456785802TH53037645406123.006304B598', $payload);
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
