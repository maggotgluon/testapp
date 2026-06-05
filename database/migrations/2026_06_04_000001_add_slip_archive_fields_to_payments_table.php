<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('slip_archived_path')->nullable()->after('slip_path');
            $table->timestamp('slip_archived_at')->nullable()->after('slip_archived_path');
            $table->timestamp('slip_deleted_at')->nullable()->after('slip_archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'slip_archived_path',
                'slip_archived_at',
                'slip_deleted_at',
            ]);
        });
    }
};
