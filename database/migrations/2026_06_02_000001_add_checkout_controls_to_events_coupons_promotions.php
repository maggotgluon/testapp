<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('payment_methods')->nullable()->after('payment_instructions');
            $table->string('description_format')->default('html')->after('description');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->boolean('show_on_checkout')->default(true)->after('is_active');
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->boolean('show_on_event_page')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['payment_methods', 'description_format']);
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('show_on_checkout');
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('show_on_event_page');
        });
    }
};
