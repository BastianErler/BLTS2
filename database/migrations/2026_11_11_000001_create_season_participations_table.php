<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('season_participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at');
            $table->timestamp('withdrawal_requested_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->foreignId('withdrawn_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('frozen_cost', 10, 2)->nullable();
            $table->timestamps();
            $table->unique(['season_id', 'user_id']);
        });

        $rows = DB::table('bets')->join('games', 'games.id', '=', 'bets.game_id')
            ->select('bets.user_id', 'games.season_id')
            ->selectRaw('MIN(bets.created_at) as first_bet_at')
            ->groupBy('bets.user_id', 'games.season_id')->get();

        foreach ($rows as $row) {
            DB::table('season_participations')->insert([
                'user_id' => $row->user_id,
                'season_id' => $row->season_id,
                'joined_at' => $row->first_bet_at ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('season_participations');
    }
};
