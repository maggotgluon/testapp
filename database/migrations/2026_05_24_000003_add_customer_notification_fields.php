<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('line_friend_status')->nullable()->after('avatar');
            $table->timestamp('line_followed_at')->nullable()->after('line_friend_status');
            $table->timestamp('line_blocked_at')->nullable()->after('line_followed_at');
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->morphs('subscribable', 'push_subscriptions_subscribable_morph_idx');
            $table->string('endpoint', 500)->unique();
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('content_encoding')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['line_friend_status', 'line_followed_at', 'line_blocked_at']);
        });
    }
};
