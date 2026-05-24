<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('show_countdown')->default(false)->after('is_published');
        });

        Schema::table('ticket_types', function (Blueprint $table) {
            $table->unsignedInteger('full_price_thb')->nullable()->after('price_thb');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->dropColumn('full_price_thb');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('show_countdown');
        });
    }
};
