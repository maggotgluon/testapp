<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('ticket_image_path')->nullable()->after('poster_path');
            $table->string('bank_name')->nullable()->after('ticket_image_path');
            $table->string('bank_account_name')->nullable()->after('bank_name');
            $table->string('bank_account_number')->nullable()->after('bank_account_name');
            $table->string('qr_payment_account_name')->nullable()->after('bank_account_number');
            $table->string('qr_payment_account')->nullable()->after('qr_payment_account_name');
            $table->string('qr_payment_image_path')->nullable()->after('qr_payment_account');
            $table->text('payment_instructions')->nullable()->after('qr_payment_image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'ticket_image_path',
                'bank_name',
                'bank_account_name',
                'bank_account_number',
                'qr_payment_account_name',
                'qr_payment_account',
                'qr_payment_image_path',
                'payment_instructions',
            ]);
        });
    }
};
