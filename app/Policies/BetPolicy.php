<?php

namespace App\Policies;

use App\Models\Bet;
use App\Models\User;

class BetPolicy
{
    public function update(User $user, Bet $bet): bool
    {
        return $bet->user_id === $user->id;
    }

    public function delete(User $user, Bet $bet): bool
    {
        return $bet->user_id === $user->id;
    }
}
