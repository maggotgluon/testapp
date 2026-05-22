<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('location_url')->nullable()->after('location');
            $table->string('hosted_by_url')->nullable()->after('hosted_by');
            $table->string('social_image_path')->nullable()->after('ticket_image_path');
            $table->text('social_description')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'location_url',
                'hosted_by_url',
                'social_image_path',
                'social_description',
            ]);
        });
    }
};
