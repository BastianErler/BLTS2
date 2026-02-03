<?php

namespace App\Observers;

use App\Models\Bet;
use App\Support\LeaderboardCache;

class BetObserver
{
    public function created(Bet $bet): void
    {
        $this->flush($bet);
    }

    public function updated(Bet $bet): void
    {
        $this->flush($bet);
    }

    public function deleted(Bet $bet): void
    {
        $this->flush($bet);
    }

    private function flush(Bet $bet): void
    {
        // season_id über Game ermitteln (ohne unnötig das ganze Model zu laden)
        $seasonId = $bet->game()->value('season_id');

        if ($seasonId) {
            LeaderboardCache::forgetSeason((int) $seasonId);
        }
    }
}
