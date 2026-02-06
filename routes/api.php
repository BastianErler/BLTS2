<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BetController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\NotificationSettingsController;
use App\Http\Controllers\Api\SeasonController;

Route::middleware('api')->group(function () {

    // ---------- Public / Auth ----------
    Route::post('/login', [AuthController::class, 'login']);

    // ---------- Protected ----------
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Seasons
        Route::get('/seasons', [SeasonController::class, 'index']);

        // Notification settings
        Route::get('/notification-settings', [NotificationSettingsController::class, 'show']);
        Route::patch('/notification-settings', [NotificationSettingsController::class, 'update']);

        // Games
        Route::get('/games', [GameController::class, 'index']);
        Route::get('/games/upcoming', [GameController::class, 'upcoming']);
        Route::get('/games/{game}', [GameController::class, 'show']);
        Route::get('/games/{game}/user-bet', [GameController::class, 'userBet']);

        // Bets
        Route::get('/bets', [BetController::class, 'index']);
        Route::post('/bets', [BetController::class, 'store']);
        Route::put('/bets/{bet}', [BetController::class, 'update']);
        Route::delete('/bets/{bet}', [BetController::class, 'destroy']);

        // Leaderboard
        Route::get('/leaderboard', [LeaderboardController::class, 'index']);
        Route::get('/leaderboard/user-stats', [LeaderboardController::class, 'userStats']);
        Route::get('/leaderboard/user-stats/{user}', [LeaderboardController::class, 'userStats']);
    });
});
