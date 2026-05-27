<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('slip_qr_status')->nullable()->after('note');
            $table->text('slip_qr_payload')->nullable()->after('slip_qr_status');
            $table->json('slip_qr_data')->nullable()->after('slip_qr_payload');
            $table->decimal('slip_qr_amount_thb', 10, 2)->nullable()->after('slip_qr_data');
            $table->timestamp('slip_qr_paid_at')->nullable()->after('slip_qr_amount_thb');
            $table->string('slip_qr_reference')->nullable()->after('slip_qr_paid_at');
            $table->string('slip_qr_receiver')->nullable()->after('slip_qr_reference');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'slip_qr_status',
                'slip_qr_payload',
                'slip_qr_data',
                'slip_qr_amount_thb',
                'slip_qr_paid_at',
                'slip_qr_reference',
                'slip_qr_receiver',
            ]);
        });
    }
};
