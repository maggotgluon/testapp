<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('beam_enabled')->default(false)->after('payment_accounts');
            $table->string('beam_fee_behavior', 20)->default('merchant_absorb')->after('beam_enabled');
            $table->decimal('beam_fee_percent', 5, 2)->nullable()->after('beam_fee_behavior');
        });

        Schema::table('ticket_orders', function (Blueprint $table) {
            $table->integer('beam_fee_thb')->nullable()->after('discount_thb');
            $table->string('beam_charge_id', 80)->nullable()->after('payment_method');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('beam_charge_id', 80)->nullable()->after('note');
            $table->text('beam_qr_image')->nullable()->after('beam_charge_id');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['beam_enabled', 'beam_fee_behavior', 'beam_fee_percent']);
        });

        Schema::table('ticket_orders', function (Blueprint $table) {
            $table->dropColumn(['beam_fee_thb', 'beam_charge_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['beam_charge_id', 'beam_qr_image']);
        });
    }
};
