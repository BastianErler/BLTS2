<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->text('endpoint');

            $table->string('p256dh', 255);
            $table->string('auth', 255);

            $table->string('content_encoding', 30)->default('aesgcm');

            $table->string('user_agent', 255)->nullable();
            $table->string('device', 50)->nullable(); // optional (ios/android/desktop)
            $table->timestamps();

            $table->unique(['user_id', 'endpoint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
