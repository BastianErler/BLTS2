<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameReviewController
{
    public function count(Request $request): JsonResponse
    {
        $seasonId = $request->query('season_id');

        $q = Game::query()->where('needs_review', true);

        if ($seasonId !== null) {
            $q->where('season_id', (int)$seasonId);
        }

        return response()->json([
            'success' => true,
            'count' => $q->count(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 50);
        $perPage = max(1, min(200, $perPage)); // clamp

        $seasonId = $request->query('season_id');

        $q = Game::query()
            ->where('needs_review', true);

        if ($seasonId !== null) {
            $q->where('season_id', (int)$seasonId);
        }

        $games = $q
            ->orderByRaw('kickoff_at is null desc') // null first
            ->orderBy('kickoff_at')
            ->orderBy('matchday')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'games' => $games,
        ]);
    }

    public function update(Request $request, Game $game): JsonResponse
    {
        $data = $request->validate([
            'kickoff_at' => ['nullable', 'date'],
            'matchday' => ['nullable', 'integer', 'min:1', 'max:99'],
            'needs_review' => ['required', 'boolean'],
        ]);

        $game->fill($data);
        $game->save();

        return response()->json([
            'success' => true,
            'game' => $game->fresh(),
        ]);
    }
}
