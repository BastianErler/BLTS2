<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BetController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\NotificationSettingsController;
use App\Http\Controllers\Api\SeasonController;

use App\Http\Controllers\Api\Admin\GameReviewController;
use App\Http\Controllers\Api\Admin\GameSyncController;
use App\Http\Controllers\Api\Admin\GameAdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('api')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public
    |--------------------------------------------------------------------------
    */
    Route::controller(AuthController::class)->group(function () {
        Route::post('/login', 'login');
    });

    /*
    |--------------------------------------------------------------------------
    | Authenticated
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        // Auth / User
        Route::controller(AuthController::class)->group(function () {
            Route::post('/logout', 'logout');
            Route::get('/me', 'me');
        });

        // Seasons
        Route::controller(SeasonController::class)->group(function () {
            Route::get('/seasons', 'index');
        });

        // Notification settings
        Route::controller(NotificationSettingsController::class)->group(function () {
            Route::get('/notification-settings', 'show');
            Route::patch('/notification-settings', 'update');
        });

        // Games
        Route::controller(GameController::class)->group(function () {
            Route::get('/games', 'index');
            Route::get('/games/upcoming', 'upcoming');
            Route::get('/games/{game}', 'show');
            Route::get('/games/{game}/user-bet', 'userBet');
        });

        // Bets
        Route::controller(BetController::class)->group(function () {
            Route::get('/bets', 'index');
            Route::post('/bets', 'store');
            Route::put('/bets/{bet}', 'update');
            Route::delete('/bets/{bet}', 'destroy');
        });

        // Leaderboard
        Route::controller(LeaderboardController::class)->group(function () {
            Route::get('/leaderboard', 'index');
            Route::get('/leaderboard/user-stats', 'userStats');
            Route::get('/leaderboard/user-stats/{user}', 'userStats');
        });

        /*
        |--------------------------------------------------------------------------
        | Admin (is_admin === true)
        |--------------------------------------------------------------------------
        */
        Route::prefix('admin')
            ->middleware('admin')
            ->group(function () {

                // Review games flagged as needs_review
                Route::controller(GameReviewController::class)->group(function () {
                    Route::get('/games/review', 'index');
                    Route::get('/games/review/count', 'count');
                    Route::patch('/games/{game}/review', 'update');
                });

                // Sync (import/resync)
                Route::controller(GameSyncController::class)->group(function () {
                    Route::get('/games/sync/status', 'status');
                    Route::post('/games/sync', 'sync');
                });

                // Admin Game Edit
                Route::controller(GameAdminController::class)->group(function () {
                    Route::patch('/games/{game}', 'update');
                });
            });
    });
});
