<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Observers\BetObserver;
use App\Observers\GameObserver;
use App\Models\Bet;
use App\Models\Game;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Bet::observe(BetObserver::class);
        Game::observe(GameObserver::class);
    }
}
