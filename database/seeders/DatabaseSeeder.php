<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['phone' => '0900000000'],
            ['name' => 'Super Admin', 'username' => 'admin', 'email' => null, 'role' => 'super_admin', 'provider' => 'guest', 'provider_id' => 'seed-admin']
        );

        User::updateOrCreate(
            ['phone' => '0911111111'],
            ['name' => 'Gate Scanner', 'username' => 'scanner', 'email' => null, 'role' => 'gate_scanner', 'provider' => 'guest', 'provider_id' => 'seed-scanner']
        );

        User::updateOrCreate(
            ['phone' => '0922222222'],
            ['name' => 'Event Admin', 'username' => 'eventadmin', 'email' => null, 'role' => 'event_admin', 'provider' => 'guest', 'provider_id' => 'seed-event-admin']
        );

        $event = Event::updateOrCreate(
            ['name' => 'Bangkok Night Market Live'],
            [
                'venue' => 'Warehouse 30',
                'location' => 'Charoen Krung, Bangkok',
                'hosted_by' => 'TicketFlow Demo',
                'description' => 'A food, music, and art night with timed early bird and VIP ticket inventory.',
                'starts_at' => now()->addWeeks(3)->setTime(18, 0),
                'ends_at' => now()->addWeeks(3)->setTime(23, 0),
                'bank_name' => 'Siam Commercial Bank',
                'bank_account_name' => 'TicketFlow Demo Co., Ltd.',
                'bank_account_number' => '123-456-7890',
                'qr_payment_account_name' => 'TicketFlow PromptPay',
                'qr_payment_account' => '081-234-5678',
                'payment_instructions' => 'Transfer the exact amount and upload your slip for admin approval.',
                'is_published' => true,
            ]
        );

        $event->ticketTypes()->updateOrCreate(
            ['name' => 'Early Bird'],
            ['description' => 'Limited early entry ticket.', 'price_thb' => 690, 'capacity' => 100, 'sale_starts_at' => now()->subDay(), 'sale_ends_at' => now()->addWeek(), 'status' => 'active']
        );

        $event->ticketTypes()->updateOrCreate(
            ['name' => 'VIP Table'],
            ['description' => 'Priority lane and table reservation.', 'price_thb' => 2500, 'capacity' => 20, 'sale_starts_at' => now()->subDay(), 'sale_ends_at' => now()->addWeeks(2), 'status' => 'active']
        );

        Coupon::updateOrCreate(
            ['code' => 'EARLYBIRD'],
            ['event_id' => $event->id, 'name' => 'Early bird launch', 'discount_type' => 'percent', 'discount_value' => 15, 'usage_limit' => 100, 'is_active' => true, 'starts_at' => now()->subDay(), 'expires_at' => now()->addWeeks(2)]
        );
    }
}
