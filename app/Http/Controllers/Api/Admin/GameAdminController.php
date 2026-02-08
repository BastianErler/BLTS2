<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GameUpdateRequest;
use App\Http\Resources\GameResource;
use App\Models\Game;

class GameAdminController extends Controller
{
    public function update(GameUpdateRequest $request, Game $game)
    {
        // Guard: bet_deadline_at ist bei euch ein Accessor (kickoff - 6h), kein DB Feld.
        if ($request->has('bet_deadline_at')) {
            return response()->json([
                'message' => 'bet_deadline_at kann nicht direkt gesetzt werden. Die Deadline wird automatisch aus kickoff_at (−6h) berechnet.',
                'field' => 'bet_deadline_at',
            ], 422);
        }

        $data = $request->validated();

        // Falls status=finished (oder bereits finished) -> wir brauchen saubere Tore, wenn sie gesetzt werden sollen.
        // In eurem Setup: finished darf korrigiert werden (Importfehler), also erlauben wir Updates immer.
        $newStatus = $data['status'] ?? $game->status;

        // Apply non-score fields first
        if (array_key_exists('kickoff_at', $data)) {
            $game->kickoff_at = $data['kickoff_at']; // cast datetime
        }

        if (array_key_exists('is_home', $data)) {
            $game->is_home = (bool) $data['is_home'];
        }

        if (array_key_exists('is_playoff', $data)) {
            $game->is_playoff = (bool) $data['is_playoff'];
        }

        if (array_key_exists('is_derby', $data)) {
            $game->is_derby = (bool) $data['is_derby'];
        }

        if (array_key_exists('status', $data)) {
            $game->status = $data['status'];
        } else {
            $game->status = $newStatus;
        }

        // Scores: nullable allowed
        $scoresTouched = false;

        if (array_key_exists('eisbaeren_goals', $data)) {
            $game->eisbaeren_goals = $data['eisbaeren_goals'];
            $scoresTouched = true;
        }

        if (array_key_exists('opponent_goals', $data)) {
            $game->opponent_goals = $data['opponent_goals'];
            $scoresTouched = true;
        }

        $game->save();

        /**
         * Repricing logic:
         * - Wenn status finished ist UND beide Tore vorhanden sind:
         *   -> Bet-Preise neu berechnen, weil Ergebnis die Preise bestimmt.
         *
         * Das gilt sowohl:
         * - beim "erstmalig" finished setzen
         * - als auch beim Korrigieren eines falschen Ergebnisses.
         */
        $isFinishedNow =
            $game->status === 'finished'
            && $game->eisbaeren_goals !== null
            && $game->opponent_goals !== null;

        if ($isFinishedNow && ($scoresTouched || $request->has('status'))) {
            // Recalculate all bet prices (but do NOT overwrite locked_at if already set)
            $game->loadMissing('bets');

            foreach ($game->bets as $bet) {
                $bet->updatePrices();

                if ($bet->locked_at === null) {
                    $bet->locked_at = now();
                }

                $bet->save();
            }
        }

        return new GameResource($game->refresh());
    }
}
