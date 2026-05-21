<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Coupon;
use App\Services\QrCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_login_rejects_admin_accounts(): void
    {
        $this->seed();

        $this->post('/login', [
            'name' => 'Super Admin',
            'phone' => '0900000000',
            'provider' => 'guest',
        ])->assertSessionHasErrors('phone');
    }

    public function test_admin_login_accepts_username_and_phone(): void
    {
        $this->seed();

        $this->post('/admin/login', [
            'username' => 'admin',
            'phone' => '0900000000',
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

    public function test_order_number_is_human_readable(): void
    {
        $this->seed();

        $response = $this->post('/orders', [
            'customer_name' => 'Demo Buyer',
            'customer_phone' => '0812345678',
            'payment_method' => 'bank_transfer',
            'items' => [
                ['ticket_type_id' => 1, 'quantity' => 1],
                ['ticket_type_id' => 2, 'quantity' => 0],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ticket_orders', [
            'order_number' => 'BNML-'.now()->format('md').'-001',
        ]);
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

        Coupon::create([
            'event_id' => 1,
            'ticket_type_id' => 1,
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
                ['ticket_type_id' => 1, 'quantity' => 2],
                ['ticket_type_id' => 2, 'quantity' => 0],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('ticket_orders', [
            'subtotal_thb' => 1380,
            'discount_thb' => 200,
            'total_thb' => 1180,
        ]);
    }
}
