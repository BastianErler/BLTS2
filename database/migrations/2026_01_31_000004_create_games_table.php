<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->integer('game_number')->nullable();
            $table->foreignId('opponent_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->boolean('is_home')->default(true)->comment('true = home game, false = away game');
            $table->dateTime('kickoff_at');
            $table->integer('eisbaeren_goals')->nullable();
            $table->integer('opponent_goals')->nullable();
            $table->enum('status', ['scheduled', 'live', 'finished', 'cancelled'])->default('scheduled');

            // Features flags
            $table->boolean('is_derby')->default(false)->comment('Important game - higher multiplier');
            $table->boolean('is_playoff')->default(false);
            $table->integer('difficulty_rating')->default(3)->comment('1-5, for dynamic pricing');

            // Reminder tracking
            $table->boolean('email_reminder_sent')->default(false);
            $table->boolean('sms_reminder_sent')->default(false);

            $table->timestamps();

            // Indexes
            $table->index(['season_id', 'kickoff_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
