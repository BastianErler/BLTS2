<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('season_winner_bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete()->comment('Predicted DEL champion');
            $table->timestamps();
            
            // One bet per user per season
            $table->unique(['user_id', 'season_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('season_winner_bets');
    }
};
