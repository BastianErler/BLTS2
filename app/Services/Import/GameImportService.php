<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\Game;
use Carbon\CarbonImmutable;

final class GameImportService
{
    /**
     * @param array<string,mixed> $data
     */
    public function upsert(array $data): Game
    {
        $seasonId   = (int) $data['season_id'];
        $opponentId = (int) $data['opponent_id'];
        $isHome     = (bool) $data['is_home'];

        $kickoff = $data['kickoff_at'] ?? null;
        if (!$kickoff instanceof CarbonImmutable) {
            throw new \InvalidArgumentException('kickoff_at must be a CarbonImmutable instance.');
        }

        $kickoffString = $kickoff->toDateTimeString();

        /** @var Game $game */
        $game = Game::query()->updateOrCreate(
            [
                'season_id'   => $seasonId,
                'opponent_id' => $opponentId,
                'is_home'     => $isHome,
                'kickoff_at'  => $kickoffString,
            ],
            [
                'matchday'        => $data['matchday'] ?? null,
                'status'          => (string) ($data['status'] ?? 'scheduled'),
                'needs_review'    => (bool) ($data['needs_review'] ?? false),
                'source'          => (string) ($data['source'] ?? 'multi'),
                'external_url'    => $data['external_url'] ?? null,
                'eisbaeren_goals' => $data['eisbaeren_goals'] ?? null,
                'opponent_goals'  => $data['opponent_goals'] ?? null,
            ],
        );

        return $game;
    }
}
