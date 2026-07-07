<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Event;
use App\Models\TicketType;
use App\Models\TicketOrder;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Models\Payment;
use App\Models\Coupon;
use App\Models\Promotion;
use App\Models\Survey;
use App\Models\CheckInLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Clean existing tables to avoid duplicate key issues or unique constraints
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        CheckInLog::truncate();
        Ticket::truncate();
        Payment::truncate();
        OrderItem::truncate();
        TicketOrder::truncate();
        Promotion::truncate();
        Coupon::truncate();
        Survey::truncate();
        TicketType::truncate();
        DB::table('event_user')->truncate();
        Event::truncate();

        // Keep or update admin users
        $superAdmin = User::updateOrCreate(
            ['phone' => '0809166690'],
            [
                'name' => 'Super Admin', 
                'username' => 'admin', 
                'email' => null, 
                'role' => 'super_admin', 
                'provider' => 'guest', 
                'provider_id' => 'seed-admin'
            ]
        );

        $scanner = User::updateOrCreate(
            ['phone' => '0809166691'],
            [
                'name' => 'Gate Scanner', 
                'username' => 'scanner', 
                'email' => null, 
                'role' => 'gate_scanner', 
                'provider' => 'guest', 
                'provider_id' => 'seed-scanner'
            ]
        );

        $eventAdmin = User::updateOrCreate(
            ['phone' => '0809166692'],
            [
                'name' => 'Event Admin', 
                'username' => 'eventadmin', 
                'email' => null, 
                'role' => 'event_admin', 
                'provider' => 'guest', 
                'provider_id' => 'seed-event-admin'
            ]
        );

        // Demo Customers
        $customer1 = User::updateOrCreate(
            ['phone' => '0811111111'],
            [
                'name' => 'John Doe', 
                'email' => 'john@example.com', 
                'role' => 'customer', 
                'provider' => 'guest', 
                'provider_id' => 'guest-0811111111'
            ]
        );

        $customer2 = User::updateOrCreate(
            ['phone' => '0822222222'],
            [
                'name' => 'Jane Smith', 
                'email' => 'jane@example.com', 
                'role' => 'customer', 
                'provider' => 'guest', 
                'provider_id' => 'guest-0822222222'
            ]
        );

        $customer3 = User::updateOrCreate(
            ['phone' => '0833333333'],
            [
                'name' => 'Bob Brown', 
                'email' => 'bob@example.com', 
                'role' => 'customer', 
                'provider' => 'guest', 
                'provider_id' => 'guest-0833333333'
            ]
        );

        $customer4 = User::updateOrCreate(
            ['phone' => '0844444444'],
            [
                'name' => 'Alice Green', 
                'email' => 'alice@example.com', 
                'role' => 'customer', 
                'provider' => 'guest', 
                'provider_id' => 'guest-0844444444'
            ]
        );

        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        // Shared payment options structure
        $paymentMethods = ['qr_payment', 'bank_transfer', 'cash'];
        $paymentAccounts = [
            [
                'key' => 'pp-qr',
                'method' => 'qr_payment',
                'label' => 'PromptPay QR / ชำระผ่าน PromptPay QR',
                'account_name' => 'TicketFlow Co., Ltd.',
                'account_number' => '0812345678',
                'instructions' => 'Scan QR, verify amount and transfer, then upload slip.',
                'is_active' => true,
            ],
            [
                'key' => 'scb-transfer',
                'method' => 'bank_transfer',
                'label' => 'SCB Bank Transfer / โอนธนาคารไทยพาณิชย์',
                'bank_name' => 'Siam Commercial Bank',
                'account_name' => 'TicketFlow Co., Ltd.',
                'account_number' => '123-4-56789-0',
                'instructions' => 'Transfer to SCB account and upload slip.',
                'is_active' => true,
            ]
        ];

        // 1. Paid Event 1: Tech Conference 2026
        $paidEvent1 = Event::create([
            'created_by' => $superAdmin->id,
            'name' => 'Tech Conference 2026',
            'venue' => 'Grand Ballroom, BITEC Bangna',
            'location' => 'BITEC Bangna, Bangkok',
            'location_url' => 'https://maps.app.goo.gl/bitec',
            'hosted_by' => 'Tech Thailand Foundation',
            'hosted_by_url' => 'https://example.com/tech-th',
            'starts_at' => now()->addWeeks(4)->setTime(9, 0),
            'ends_at' => now()->addWeeks(4)->setTime(17, 0),
            'description' => 'The ultimate developer and tech conference in Thailand, featuring modern architectures, AI, cloud engineering, and web technology updates.',
            'social_description' => 'Join Tech Conference 2026 at BITEC for cutting-edge technology keynotes.',
            'payment_instructions' => 'Transfer within 24 hours of booking.',
            'payment_methods' => $paymentMethods,
            'payment_accounts' => $paymentAccounts,
            'is_published' => true,
            'show_countdown' => true,
        ]);

        $techVip = $paidEvent1->ticketTypes()->create([
            'name' => 'VIP Pass',
            'description' => 'Includes front-row seating, lunch, and networking session access.',
            'price_thb' => 2500,
            'full_price_thb' => 3000,
            'capacity' => 50,
            'sold_count' => 0,
            'sale_starts_at' => now()->subDays(2),
            'sale_ends_at' => now()->addWeeks(3),
            'status' => 'active',
        ]);

        $techRegular = $paidEvent1->ticketTypes()->create([
            'name' => 'Regular Pass',
            'description' => 'Standard access to all conference tracks.',
            'price_thb' => 1000,
            'full_price_thb' => 1200,
            'capacity' => 200,
            'sold_count' => 0,
            'sale_starts_at' => now()->subDays(2),
            'sale_ends_at' => now()->addWeeks(3),
            'status' => 'active',
        ]);

        $techStudent = $paidEvent1->ticketTypes()->create([
            'name' => 'Student Pass',
            'description' => 'Discounted entry for verified student ID card holders.',
            'price_thb' => 500,
            'capacity' => 100,
            'sold_count' => 0,
            'sale_starts_at' => now()->subDays(2),
            'sale_ends_at' => now()->addWeeks(3),
            'status' => 'active',
        ]);

        // 2. Paid Event 2: Music Festival 2026
        $paidEvent2 = Event::create([
            'created_by' => $superAdmin->id,
            'name' => 'Music Festival 2026',
            'venue' => 'Lakeside Field, Muang Thong Thani',
            'location' => 'Muang Thong Thani, Nonthaburi',
            'location_url' => 'https://maps.app.goo.gl/lake-mtt',
            'hosted_by' => 'Beatwave Entertainment',
            'hosted_by_url' => 'https://example.com/beatwave',
            'starts_at' => now()->addWeeks(6)->setTime(16, 0),
            'ends_at' => now()->addWeeks(6)->setTime(23, 30),
            'description' => 'An outdoor electronic and indie music festival by the lake, with local and international artist line-ups.',
            'social_description' => 'Lakeside dance vibes are back! Get your tickets for Music Festival 2026 now.',
            'payment_instructions' => 'Attach transfer slip during checkout.',
            'payment_methods' => $paymentMethods,
            'payment_accounts' => $paymentAccounts,
            'is_published' => true,
            'show_countdown' => true,
        ]);

        $musicVip = $paidEvent2->ticketTypes()->create([
            'name' => 'VIP Ticket',
            'description' => 'Fast lane entry, VIP deck zone, and dedicated beverage bar.',
            'price_thb' => 3500,
            'capacity' => 50,
            'sold_count' => 0,
            'status' => 'active',
        ]);

        $musicGa = $paidEvent2->ticketTypes()->create([
            'name' => 'General Admission',
            'description' => 'Lakeside standing zone entry.',
            'price_thb' => 1500,
            'capacity' => 500,
            'sold_count' => 0,
            'status' => 'active',
        ]);

        $musicEarly = $paidEvent2->ticketTypes()->create([
            'name' => 'Early Entry',
            'description' => 'Must enter before 17:30 PM.',
            'price_thb' => 1200,
            'capacity' => 150,
            'sold_count' => 0,
            'status' => 'active',
        ]);

        // 3. Paid Event 3: Cooking Masterclass
        $paidEvent3 = Event::create([
            'created_by' => $superAdmin->id,
            'name' => 'Cooking Masterclass',
            'venue' => 'Culinary Art Studio, Bangkok',
            'location' => 'Siam Paragon, Bangkok',
            'location_url' => 'https://maps.app.goo.gl/siam-paragon',
            'hosted_by' => 'Chef Table Academy',
            'hosted_by_url' => 'https://example.com/chef-academy',
            'starts_at' => now()->addWeeks(2)->setTime(10, 0),
            'ends_at' => now()->addWeeks(2)->setTime(15, 0),
            'description' => 'Learn how to cook authentic Thai fine dining cuisine with a Michelin-starred chef in this hands-on workshop.',
            'social_description' => 'Upgrade your kitchen skills at the Cooking Masterclass.',
            'payment_instructions' => 'Only 20 seats per session. Book now.',
            'payment_methods' => $paymentMethods,
            'payment_accounts' => $paymentAccounts,
            'is_published' => true,
            'show_countdown' => false,
        ]);

        $cookMorning = $paidEvent3->ticketTypes()->create([
            'name' => 'Morning Session',
            'description' => '10:00 AM - 12:00 PM. Focus on appetizers and curries.',
            'price_thb' => 1800,
            'capacity' => 20,
            'sold_count' => 0,
            'status' => 'active',
        ]);

        $cookAfternoon = $paidEvent3->ticketTypes()->create([
            'name' => 'Afternoon Session',
            'description' => '13:00 PM - 15:00 PM. Focus on main courses and desserts.',
            'price_thb' => 1800,
            'capacity' => 20,
            'sold_count' => 0,
            'status' => 'active',
        ]);

        // 4. Free Event 4: Community Run 2026
        $freeEvent4 = Event::create([
            'created_by' => $superAdmin->id,
            'name' => 'Community Run 2026',
            'venue' => 'Lumpini Park, Bangkok',
            'location' => 'Lumpini Park, Bangkok',
            'location_url' => 'https://maps.app.goo.gl/lumpini',
            'hosted_by' => 'Healthy Bangkok Org',
            'hosted_by_url' => 'https://example.com/healthy-bkk',
            'starts_at' => now()->addWeeks(3)->setTime(6, 0),
            'ends_at' => now()->addWeeks(3)->setTime(10, 0),
            'description' => 'A free charity 5K/10K run for everyone. Join us to promote wellness and community health.',
            'social_description' => 'Register for free at the Lumpini Park Community Run 2026.',
            'payment_instructions' => 'Free registration ticket.',
            'payment_methods' => ['cash'],
            'payment_accounts' => [],
            'is_published' => true,
            'show_countdown' => true,
        ]);

        $freeRegister = $freeEvent4->ticketTypes()->create([
            'name' => 'Free Registration',
            'description' => 'General entry run registration. Receive BIB and runner shirt.',
            'price_thb' => 0,
            'capacity' => 1000,
            'sold_count' => 0,
            'status' => 'active',
        ]);

        // Sync admin permissions
        $paidEvent1->assignedUsers()->syncWithoutDetaching([$scanner->id, $eventAdmin->id]);
        $paidEvent2->assignedUsers()->syncWithoutDetaching([$scanner->id, $eventAdmin->id]);
        $paidEvent3->assignedUsers()->syncWithoutDetaching([$scanner->id, $eventAdmin->id]);
        $freeEvent4->assignedUsers()->syncWithoutDetaching([$scanner->id, $eventAdmin->id]);


        // --- SEED SURVEYS (2 Placements/Scopes) ---
        // 1. Survey 1 (Global scope, placement = on_login)
        Survey::create([
            'event_id' => null,
            'created_by' => $superAdmin->id,
            'title' => 'Welcome Survey / แบบสำรวจผู้ใช้ใหม่',
            'description' => 'Tell us a bit about your event interests to help us serve you better.',
            'placement' => 'on_login',
            'is_active' => true,
            'questions' => [
                [
                    'id' => 'q_hear',
                    'type' => 'text',
                    'label' => 'How did you hear about TicketFlow? / รู้จักพวกเราจากช่องทางไหน?',
                    'required' => true,
                ],
                [
                    'id' => 'q_interests',
                    'type' => 'choice',
                    'label' => 'What types of events do you prefer to attend? / สนใจงานอีเวนต์ประเภทใด?',
                    'options' => ['Technology & Startups', 'Music & Concerts', 'Culinary & Cooking Workshop', 'Sports & Wellness'],
                    'multiple' => true,
                    'required' => false,
                ]
            ],
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYear(),
        ]);

        // 2. Survey 2 (Event-specific, placement = before_payment)
        Survey::create([
            'event_id' => $paidEvent1->id,
            'created_by' => $superAdmin->id,
            'title' => 'Conference Attendee Survey / ข้อมูลสำหรับงานสัมมนา',
            'description' => 'Please provide quick professional information before checkout.',
            'placement' => 'before_payment',
            'is_active' => true,
            'questions' => [
                [
                    'id' => 'q_job',
                    'type' => 'text',
                    'label' => 'What is your job title / profession? / งานหลักหรือตำแหน่งงานของคุณ?',
                    'required' => true,
                ],
                [
                    'id' => 'q_rating',
                    'type' => 'rating',
                    'label' => 'Rate your level of excitement for AI talks (1-5) / ระดับความสนใจในการบรรยายด้าน AI (1-5)',
                    'min' => 1,
                    'max' => 5,
                    'required' => false,
                ]
            ],
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYear(),
        ]);


        // --- SEED COUPONS (Combinations of scope, type, and options) ---
        // 1. Global + Percent + Order scope (Show on checkout)
        Coupon::create([
            'event_id' => null,
            'ticket_type_id' => null,
            'name' => 'Global 10% Off Order',
            'code' => 'GLOBAL10',
            'discount_type' => 'percent',
            'discount_scope' => 'order',
            'discount_value' => 10,
            'usage_limit' => 500,
            'used_count' => 0,
            'is_active' => true,
            'show_on_checkout' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonths(6),
        ]);

        // 2. Global + Fixed + Order scope (Show on checkout)
        Coupon::create([
            'event_id' => null,
            'ticket_type_id' => null,
            'name' => '50 Baht Global Discount',
            'code' => 'GLOBAL50',
            'discount_type' => 'fixed',
            'discount_scope' => 'order',
            'discount_value' => 50,
            'usage_limit' => 1000,
            'used_count' => 0,
            'is_active' => true,
            'show_on_checkout' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonths(6),
        ]);

        // 3. Event-Specific + Percent + Item scope
        Coupon::create([
            'event_id' => $paidEvent1->id,
            'ticket_type_id' => null,
            'name' => 'Tech Event 20% Off Per Ticket',
            'code' => 'TECH20',
            'discount_type' => 'percent',
            'discount_scope' => 'item',
            'discount_value' => 20,
            'usage_limit' => 100,
            'used_count' => 0,
            'is_active' => true,
            'show_on_checkout' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonths(6),
        ]);

        // 4. Event-Specific + Fixed + Order scope
        Coupon::create([
            'event_id' => $paidEvent2->id,
            'ticket_type_id' => null,
            'name' => 'Music Event 300 Baht Off',
            'code' => 'MUSIC300',
            'discount_type' => 'fixed',
            'discount_scope' => 'order',
            'discount_value' => 300,
            'usage_limit' => 50,
            'used_count' => 0,
            'is_active' => true,
            'show_on_checkout' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonths(6),
        ]);

        // 5. Ticket-Type-Specific + Fixed + Item scope
        Coupon::create([
            'event_id' => $paidEvent1->id,
            'ticket_type_id' => $techVip->id,
            'name' => '500 Baht Off VIP Pass',
            'code' => 'VIPDISCOUNT',
            'discount_type' => 'fixed',
            'discount_scope' => 'item',
            'discount_value' => 500,
            'usage_limit' => 20,
            'used_count' => 0,
            'is_active' => true,
            'show_on_checkout' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonths(6),
        ]);

        // 6. Expired / Inactive coupon
        Coupon::create([
            'event_id' => null,
            'ticket_type_id' => null,
            'name' => 'Expired Coupon Code',
            'code' => 'EXPIRED',
            'discount_type' => 'percent',
            'discount_scope' => 'order',
            'discount_value' => 15,
            'usage_limit' => 100,
            'used_count' => 100, // fully used
            'is_active' => false,
            'show_on_checkout' => true,
            'starts_at' => now()->subMonths(2),
            'expires_at' => now()->subDay(),
        ]);

        // 7. Hidden from Checkout coupon
        Coupon::create([
            'event_id' => null,
            'ticket_type_id' => null,
            'name' => 'Secret 50% Off Global',
            'code' => 'SECRET50',
            'discount_type' => 'percent',
            'discount_scope' => 'order',
            'discount_value' => 50,
            'usage_limit' => 10,
            'used_count' => 0,
            'is_active' => true,
            'show_on_checkout' => false, // Hidden
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonths(6),
        ]);


        // --- SEED PROMOTIONS (Combinations of type, scope, limits) ---
        // 1. Buy X Get Y (Ticket-Type-Specific)
        Promotion::create([
            'event_id' => $paidEvent1->id,
            'ticket_type_id' => $techVip->id,
            'name' => 'Buy 2 VIP Passes Get 1 Free',
            'description' => 'Invite your friends. Purchase 2 VIP tickets and get another 1 completely free.',
            'promotion_type' => 'buy_x_get_y',
            'discount_scope' => 'item',
            'buy_quantity' => 2,
            'get_quantity' => 1,
            'min_quantity' => 3,
            'is_active' => true,
            'show_on_event_page' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonths(6),
        ]);

        // 2. Percentage + Item scope (min quantity filter)
        Promotion::create([
            'event_id' => $paidEvent2->id,
            'ticket_type_id' => $musicGa->id,
            'name' => 'Music GA Group Ticket 15% Off',
            'description' => 'Get 15% off when buying 3 or more General Admission tickets.',
            'promotion_type' => 'percent',
            'discount_scope' => 'item',
            'discount_value' => 15,
            'min_quantity' => 3,
            'is_active' => true,
            'show_on_event_page' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonths(6),
        ]);

        // 3. Percentage + Order scope (Combines with coupons)
        Promotion::create([
            'event_id' => null,
            'ticket_type_id' => null,
            'name' => 'Global 10% Off (Combinable)',
            'description' => 'Standard promotion applied automatically at checkout. Can stack with coupons.',
            'promotion_type' => 'percent',
            'discount_scope' => 'order',
            'discount_value' => 10,
            'combines_with_coupons' => true,
            'is_active' => true,
            'show_on_event_page' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonths(6),
        ]);

        // 4. Fixed + Order scope (Does NOT combine with coupons)
        Promotion::create([
            'event_id' => null,
            'ticket_type_id' => null,
            'name' => 'Flat 200 THB Off Order (Exclusive)',
            'description' => 'Get 200 THB off your order instantly. Exclusive offer - cannot be stacked with coupons.',
            'promotion_type' => 'fixed',
            'discount_scope' => 'order',
            'discount_value' => 200,
            'combines_with_coupons' => false,
            'is_active' => true,
            'show_on_event_page' => false, // Hidden banner
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonths(6),
        ]);

        // 5. Max Discount Limit set
        Promotion::create([
            'event_id' => null,
            'ticket_type_id' => null,
            'name' => '50% Mega Promo (Capped at 500 Baht)',
            'description' => 'Save half on your purchase, up to a maximum discount of 500 THB.',
            'promotion_type' => 'percent',
            'discount_scope' => 'order',
            'discount_value' => 50,
            'max_discount_thb' => 500,
            'is_active' => true,
            'show_on_event_page' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonths(6),
        ]);

        // 6. Inactive promotion
        Promotion::create([
            'event_id' => null,
            'ticket_type_id' => null,
            'name' => 'Draft / Inactive Promo',
            'description' => 'This promo will not be applied.',
            'promotion_type' => 'percent',
            'discount_scope' => 'order',
            'discount_value' => 20,
            'is_active' => false,
            'show_on_event_page' => true,
            'starts_at' => now()->subMonths(1),
            'expires_at' => now()->subDay(),
        ]);


        // --- SEED DEMO ORDERS AND TICKETS ---
        
        // 1. Order 1: Approved Order (Tech Conference VIP Pass)
        // Subtotal = 2500, Discount = 250 (10% global coupon), Total = 2250
        $order1 = TicketOrder::create([
            'order_number' => 'TECH-0706-001',
            'user_id' => $customer1->id,
            'customer_name' => $customer1->name,
            'customer_phone' => $customer1->phone,
            'customer_email' => $customer1->email,
            'status' => 'approved',
            'subtotal_thb' => 2500,
            'discount_thb' => 250,
            'total_thb' => 2250,
            'payment_method' => 'qr_payment',
            'payment_note' => 'Please approve ASAP.',
            'payment_slip_path' => 'payment-slips/demo_slip_john.jpg',
            'approved_at' => now()->subHours(5),
            'approved_by' => $eventAdmin->id,
        ]);

        $orderItem1 = $order1->items()->create([
            'event_id' => $paidEvent1->id,
            'ticket_type_id' => $techVip->id,
            'quantity' => 1,
            'unit_price_thb' => 2500,
            'line_total_thb' => 2500,
        ]);

        $ticket1 = $order1->tickets()->create([
            'uuid' => (string) Str::uuid(),
            'order_item_id' => $orderItem1->id,
            'event_id' => $paidEvent1->id,
            'ticket_type_id' => $techVip->id,
            'user_id' => $customer1->id,
            'holder_name' => $customer1->name,
            'holder_phone' => $customer1->phone,
            'status' => 'checked_in', // This one will have scan logs
            'checked_in_at' => now()->subHours(2),
        ]);

        $techVip->increment('sold_count');

        Payment::create([
            'ticket_order_id' => $order1->id,
            'method' => 'qr_payment',
            'payment_account_key' => 'pp-qr',
            'payment_account_label' => 'PromptPay QR',
            'payment_account_name' => 'TicketFlow Co., Ltd.',
            'payment_account_number' => '0812345678',
            'amount_thb' => 2250,
            'expected_amount_thb' => 2250,
            'expected_promptpay_id' => '0812345678',
            'status' => 'approved',
            'slip_path' => 'payment-slips/demo_slip_john.jpg',
            'slip_qr_status' => 'decoded',
            'slip_qr_payload' => '00020101021229370016A000000677010111011300668123456785802TH54072250.0053037646304ABCD',
            'slip_qr_amount_thb' => 2250.00,
            'slip_qr_paid_at' => now()->subHours(6),
            'slip_qr_reference' => '123456789XYZ',
            'slip_qr_reference_normalized' => '123456789XYZ',
            'slip_qr_receiver' => '0812345678',
            'slip_review_status' => 'ok',
            'slip_reviewed_at' => now()->subHours(5),
        ]);

        // Add a scan entry for Ticket 1
        CheckInLog::create([
            'ticket_id' => $ticket1->id,
            'scanned_by' => $scanner->id,
            'action' => 'check_in',
            'gate' => 'Main Gate A',
            'note' => 'Automatic gate check-in via seeder.',
        ]);


        // 2. Order 2: Pending Order (Music Festival 2026)
        // Subtotal = 1500, Discount = 0, Total = 1500
        $order2 = TicketOrder::create([
            'order_number' => 'MUSI-0706-001',
            'user_id' => $customer2->id,
            'customer_name' => $customer2->name,
            'customer_phone' => $customer2->phone,
            'customer_email' => $customer2->email,
            'status' => 'pending',
            'subtotal_thb' => 1500,
            'discount_thb' => 0,
            'total_thb' => 1500,
            'payment_method' => 'bank_transfer',
            'payment_slip_path' => 'payment-slips/demo_slip_jane.jpg',
        ]);

        $orderItem2 = $order2->items()->create([
            'event_id' => $paidEvent2->id,
            'ticket_type_id' => $musicGa->id,
            'quantity' => 1,
            'unit_price_thb' => 1500,
            'line_total_thb' => 1500,
        ]);

        $order2->tickets()->create([
            'uuid' => (string) Str::uuid(),
            'order_item_id' => $orderItem2->id,
            'event_id' => $paidEvent2->id,
            'ticket_type_id' => $musicGa->id,
            'user_id' => $customer2->id,
            'holder_name' => $customer2->name,
            'holder_phone' => $customer2->phone,
            'status' => 'pending',
        ]);

        Payment::create([
            'ticket_order_id' => $order2->id,
            'method' => 'bank_transfer',
            'payment_account_key' => 'scb-transfer',
            'payment_account_label' => 'SCB Bank Transfer',
            'payment_account_name' => 'TicketFlow Co., Ltd.',
            'payment_account_number' => '123-4-56789-0',
            'amount_thb' => 1500,
            'expected_amount_thb' => 1500,
            'status' => 'submitted',
            'slip_path' => 'payment-slips/demo_slip_jane.jpg',
            'slip_qr_status' => 'no_qr', // Standard bank receipt screenshot
            'slip_review_status' => 'needs_manual_review',
        ]);


        // 3. Order 3: Rejected / Cancelled Order (Cooking Masterclass)
        // Subtotal = 1800, Discount = 0, Total = 1800
        $order3 = TicketOrder::create([
            'order_number' => 'COOK-0706-001',
            'user_id' => $customer3->id,
            'customer_name' => $customer3->name,
            'customer_phone' => $customer3->phone,
            'customer_email' => $customer3->email,
            'status' => 'rejected',
            'subtotal_thb' => 1800,
            'discount_thb' => 0,
            'total_thb' => 1800,
            'payment_method' => 'qr_payment',
            'payment_slip_path' => 'payment-slips/demo_slip_bob.jpg',
        ]);

        $orderItem3 = $order3->items()->create([
            'event_id' => $paidEvent3->id,
            'ticket_type_id' => $cookMorning->id,
            'quantity' => 1,
            'unit_price_thb' => 1800,
            'line_total_thb' => 1800,
        ]);

        $order3->tickets()->create([
            'uuid' => (string) Str::uuid(),
            'order_item_id' => $orderItem3->id,
            'event_id' => $paidEvent3->id,
            'ticket_type_id' => $cookMorning->id,
            'user_id' => $customer3->id,
            'holder_name' => $customer3->name,
            'holder_phone' => $customer3->phone,
            'status' => 'rejected',
        ]);

        Payment::create([
            'ticket_order_id' => $order3->id,
            'method' => 'qr_payment',
            'payment_account_key' => 'pp-qr',
            'payment_account_label' => 'PromptPay QR',
            'amount_thb' => 1800,
            'expected_amount_thb' => 1800,
            'status' => 'rejected',
            'slip_path' => 'payment-slips/demo_slip_bob.jpg',
            'slip_review_status' => 'needs_manual_review',
        ]);


        // 4. Order 4: Approved Free Order (Community Run 2026)
        // Subtotal = 0, Discount = 0, Total = 0
        $order4 = TicketOrder::create([
            'order_number' => 'COMM-0706-001',
            'user_id' => $customer4->id,
            'customer_name' => $customer4->name,
            'customer_phone' => $customer4->phone,
            'customer_email' => $customer4->email,
            'status' => 'approved',
            'subtotal_thb' => 0,
            'discount_thb' => 0,
            'total_thb' => 0,
            'payment_method' => 'cash',
            'approved_at' => now()->subHours(1),
            'approved_by' => null,
        ]);

        $orderItem4 = $order4->items()->create([
            'event_id' => $freeEvent4->id,
            'ticket_type_id' => $freeRegister->id,
            'quantity' => 1,
            'unit_price_thb' => 0,
            'line_total_thb' => 0,
        ]);

        $order4->tickets()->create([
            'uuid' => (string) Str::uuid(),
            'order_item_id' => $orderItem4->id,
            'event_id' => $freeEvent4->id,
            'ticket_type_id' => $freeRegister->id,
            'user_id' => $customer4->id,
            'holder_name' => $customer4->name,
            'holder_phone' => $customer4->phone,
            'status' => 'approved',
        ]);

        $freeRegister->increment('sold_count');

        Payment::create([
            'ticket_order_id' => $order4->id,
            'method' => 'cash',
            'amount_thb' => 0,
            'expected_amount_thb' => 0,
            'status' => 'waived',
        ]);
    }
}

