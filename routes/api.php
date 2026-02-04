<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BetController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\SeasonController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AppConfigController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\PushTestController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::options('{any}', function () {
    return response()->json([], 204);
})->where('any', '.*');

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/app-config', [AppConfigController::class, 'show'])->name('api.app-config');

    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store']);


    Route::post('/push/test', [PushTestController::class, 'send'])
        ->name('api.push.test');

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me/settings', [AuthController::class, 'updateSettings']);


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

    //Season
    Route::get('/seasons', [SeasonController::class, 'index']);
});

Route::post('/push-debug', function (Request $request) {
    Log::info('SW push-debug', $request->all());
    return response()->json(['ok' => true]);
});
