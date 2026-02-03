<?php

namespace App\Observers;

use App\Models\Game;
use App\Support\LeaderboardCache;

class GameObserver
{
    public function updated(Game $game): void
    {
        // Wenn sich irgendwas am Spiel ändert (status, kickoff_at, scores),
        // kann das Leaderboard/Deltas beeinflussen.
        if ($game->season_id) {
            LeaderboardCache::forgetSeason((int) $game->season_id);
        }
    }

    public function created(Game $game): void
    {
        if ($game->season_id) {
            LeaderboardCache::forgetSeason((int) $game->season_id);
        }
    }

    public function deleted(Game $game): void
    {
        if ($game->season_id) {
            LeaderboardCache::forgetSeason((int) $game->season_id);
        }
    }
}
