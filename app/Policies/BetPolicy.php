<?php

namespace App\Policies;

use App\Models\Bet;
use App\Models\SeasonParticipation;
use App\Models\User;

class BetPolicy
{
    public function update(User $user, Bet $bet): bool
    {
        return $bet->user_id === $user->id && $this->isParticipant($user, $bet);
    }

    public function delete(User $user, Bet $bet): bool
    {
        return $bet->user_id === $user->id && $this->isParticipant($user, $bet);
    }

    private function isParticipant(User $user, Bet $bet): bool
    {
        return SeasonParticipation::where('user_id', $user->id)
            ->where('season_id', $bet->game->season_id)
            ->whereNull('withdrawn_at')->exists();
    }
}
