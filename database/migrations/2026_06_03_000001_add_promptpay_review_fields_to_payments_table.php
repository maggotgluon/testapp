<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_account_key')->nullable()->after('method');
            $table->string('payment_account_label')->nullable()->after('payment_account_key');
            $table->string('payment_account_name')->nullable()->after('payment_account_label');
            $table->string('payment_account_number')->nullable()->after('payment_account_name');
            $table->decimal('expected_amount_thb', 10, 2)->nullable()->after('amount_thb');
            $table->string('expected_promptpay_id')->nullable()->after('expected_amount_thb');
            $table->string('slip_image_sha256', 64)->nullable()->after('slip_path');
            $table->string('slip_qr_payload_sha256', 64)->nullable()->after('slip_qr_payload');
            $table->string('slip_qr_reference_normalized')->nullable()->after('slip_qr_reference');
            $table->string('slip_review_status')->nullable()->after('slip_qr_receiver');
            $table->json('slip_review_flags')->nullable()->after('slip_review_status');
            $table->timestamp('slip_reviewed_at')->nullable()->after('slip_review_flags');

            $table->index('slip_image_sha256');
            $table->index('slip_qr_payload_sha256');
            $table->index('slip_qr_reference_normalized');
            $table->index('slip_review_status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['slip_image_sha256']);
            $table->dropIndex(['slip_qr_payload_sha256']);
            $table->dropIndex(['slip_qr_reference_normalized']);
            $table->dropIndex(['slip_review_status']);
            $table->dropColumn([
                'payment_account_key',
                'payment_account_label',
                'payment_account_name',
                'payment_account_number',
                'expected_amount_thb',
                'expected_promptpay_id',
                'slip_image_sha256',
                'slip_qr_payload_sha256',
                'slip_qr_reference_normalized',
                'slip_review_status',
                'slip_review_flags',
                'slip_reviewed_at',
            ]);
        });
    }
};
