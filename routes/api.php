<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BetController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\LeaderboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/settings', [AuthController::class, 'updateSettings']);

    // Games
    Route::get('/games', [GameController::class, 'index']);
    Route::get('/games/upcoming', [GameController::class, 'upcoming']);
    Route::get('/games/{game}', [GameController::class, 'show']);
    Route::get('/games/{game}/user-bet', [GameController::class, 'userBet']);
    Route::get('/games/{game}/bets', [BetController::class, 'forGame']);

    // Bets
    Route::get('/bets', [BetController::class, 'index']);
    Route::post('/bets', [BetController::class, 'store']);
    Route::put('/bets/{bet}', [BetController::class, 'update']);
    Route::delete('/bets/{bet}', [BetController::class, 'destroy']);

    // Leaderboard
    Route::get('/leaderboard', [LeaderboardController::class, 'index']);
    Route::get('/stats', [LeaderboardController::class, 'userStats']);
    Route::get('/stats/{user}', [LeaderboardController::class, 'userStats']);
});
