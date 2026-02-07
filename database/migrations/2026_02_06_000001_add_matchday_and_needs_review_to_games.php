<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            if (!Schema::hasColumn('games', 'matchday')) {
                $table->unsignedSmallInteger('matchday')->nullable()->after('season_id');
            }
            if (!Schema::hasColumn('games', 'needs_review')) {
                $table->boolean('needs_review')->default(false)->after('status');
            }

            // Unique key for "upcoming identity"
            // NOTE: multiple NULL matchday are allowed in MySQL unique indexes.
            $table->unique(['season_id', 'matchday', 'opponent_id', 'is_home'], 'games_unique_matchday_pairing');
            $table->index(['season_id', 'opponent_id', 'is_home'], 'games_pairing_idx');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropUnique('games_unique_matchday_pairing');
            $table->dropIndex('games_pairing_idx');

            if (Schema::hasColumn('games', 'matchday')) {
                $table->dropColumn('matchday');
            }
            if (Schema::hasColumn('games', 'needs_review')) {
                $table->dropColumn('needs_review');
            }
        });
    }
};
