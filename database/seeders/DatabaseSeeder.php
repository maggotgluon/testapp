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
            ['phone' => '0809166690'],
            ['name' => 'Super Admin', 'username' => 'admin', 'email' => null, 'role' => 'super_admin', 'provider' => 'guest', 'provider_id' => 'seed-admin']
        );

        User::updateOrCreate(
            ['phone' => '0809166691'],
            ['name' => 'Gate Scanner', 'username' => 'scanner', 'email' => null, 'role' => 'gate_scanner', 'provider' => 'guest', 'provider_id' => 'seed-scanner']
        );

        User::updateOrCreate(
            ['phone' => '0809166692'],
            ['name' => 'Event Admin', 'username' => 'eventadmin', 'email' => null, 'role' => 'event_admin', 'provider' => 'guest', 'provider_id' => 'seed-event-admin']
        );

        $event = Event::updateOrCreate(
            ['name' => 'SHIMMER & SHINE'],
            [
                'venue' => 'Meeting Room, Golden Sea Hotel',
                'location' => 'Golden Sea Hotel Hua Hin',
                'hosted_by' => 'Zumba Hua Hin',
                'description' => 'Zumba Hua Hin is thrilled to present SHIMMER & SHINE, a vibrant Zumba event that promises an unforgettable experience filled with energy, music, and dance. Join us for an exhilarating day of movement and fun as we bring together the best of Zumba in a lively and colorful atmosphere. Whether you\'re a seasoned Zumba enthusiast or new to the world of dance fitness, SHIMMER & SHINE offers something for everyone. Get ready to dance, sweat, and shine with us at this one-of-a-kind event!',
                'starts_at' => now()->addWeeks(3)->setTime(18, 0),
                'ends_at' => now()->addWeeks(3)->setTime(23, 0),
                'bank_name' => 'Krungthai Bank',
                'bank_account_name' => 'Worasuda Worachartudomphong',
                'bank_account_number' => '956-0-56414-5',
                'qr_payment_account_name' => 'Worasuda PromptPay',
                'qr_payment_account' => '063-147-9799',
                'payment_instructions' => 'Transfer the exact amount and upload your slip for admin approval.',
                'is_published' => true,
            ]
        );

        $event->ticketTypes()->updateOrCreate(
            ['name' => 'Flash Sale'],
            ['description' => 'Limited early entry ticket.', 
            'price_thb' => 199, 'capacity' => 50, 'sale_starts_at' => now()->subDay(), 'sale_ends_at' => now(), 'status' => 'active']
        );

        $event->ticketTypes()->updateOrCreate(
            ['name' => 'Early Bird'],
            ['description' => 'Limited early entry ticket.', 
            'price_thb' => 399, 'capacity' => 100, 'sale_starts_at' => now(), 'sale_ends_at' => now()->addWeek(), 'status' => 'active']
        );

        $event->ticketTypes()->updateOrCreate(
            ['name' => 'Regular entry'],
            ['description' => 'General admission ticket.', 'price_thb' => 499, 'capacity' => 200, 'sale_starts_at' => now()->addWeek(), 'sale_ends_at' => now()->addWeeks(2), 'status' => 'active']
        );

        Coupon::updateOrCreate(
            ['code' => 'EARLYBIRD'],
            ['event_id' => $event->id, 'ticket_type_id' => null, 'name' => 'Early bird launch', 'discount_type' => 'percent', 'discount_scope' => 'order', 'discount_value' => 15, 'usage_limit' => 100, 'is_active' => true, 'starts_at' => now()->subDay(), 'expires_at' => now()->addWeeks(2)]
        );
    }
}
