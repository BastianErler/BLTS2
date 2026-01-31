# 🎯 Tippspiel - Vue SPA + Laravel API

## Architecture Overview

```
┌─────────────────────┐         ┌─────────────────────┐
│   Vue 3 SPA         │         │   Laravel API       │
│   (Port 5173)       │────────▶│   (Port 8000)       │
│                     │  HTTP   │                     │
│  - Vue Router       │  Axios  │  - Sanctum Auth     │
│  - Pinia (State)    │         │  - RESTful API      │
│  - Axios            │         │  - MySQL Database   │
└─────────────────────┘         └─────────────────────┘
```

## Quick Start

```bash
# 1. Install Vue + API setup
bash setup-vue-api.sh

# 2. Start Laravel API
php artisan serve

# 3. In new terminal: Start Vue dev server
npm run dev
```

## Database Structure (Example)

### Tables you'll need:

**users**

- id
- name
- email
- password
- points (total)

**matches**

- id
- team_home
- team_away
- score_home (nullable)
- score_away (nullable)
- kickoff_at
- status (upcoming/live/finished)

**bets**

- id
- user_id
- match_id
- predicted_home
- predicted_away
- points_earned (nullable)
- created_at

**leagues** (optional)

- id
- name
- code (join code)
- creator_id

**league_user** (pivot)

- league_id
- user_id

## API Routes Structure

```php
// routes/api.php

// Public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Matches
    Route::apiResource('matches', MatchController::class);

    // Bets
    Route::post('/bets', [BetController::class, 'store']);
    Route::get('/bets', [BetController::class, 'index']);

    // Leaderboard
    Route::get('/leaderboard', [LeaderboardController::class, 'index']);
});
```

## Vue Project Structure

```
resources/vue/
├── src/
│   ├── components/
│   │   ├── MatchCard.vue
│   │   ├── BetForm.vue
│   │   └── Leaderboard.vue
│   ├── views/
│   │   ├── Home.vue
│   │   ├── Matches.vue
│   │   ├── MyBets.vue
│   │   └── Login.vue
│   ├── stores/
│   │   ├── auth.js
│   │   └── matches.js
│   ├── services/
│   │   └── api.js
│   ├── router/
│   │   └── index.js
│   ├── App.vue
│   └── main.js
├── index.html
└── public/
```

## Features to Implement

### Phase 1 - MVP

- [ ] User Registration/Login
- [ ] View upcoming matches
- [ ] Place bets before kickoff
- [ ] See results after match
- [ ] Basic leaderboard

### Phase 2 - Enhanced

- [ ] Live match updates
- [ ] Point system (exact: 3pts, tendency: 1pt)
- [ ] User profiles
- [ ] Match notifications
- [ ] Private leagues

### Phase 3 - Advanced

- [ ] Real-time updates (Pusher/WebSockets)
- [ ] Match statistics
- [ ] Achievements/Badges
- [ ] Social features (comments)
- [ ] Mobile app (PWA)

## Commands for Development

```bash
# Create models with migration
php artisan make:model Match -m
php artisan make:model Bet -m

# Create API controllers
php artisan make:controller Api/MatchController --api
php artisan make:controller Api/BetController --api

# Create seeders (for test data)
php artisan make:seeder MatchSeeder

# Run migrations
php artisan migrate

# Seed test data
php artisan db:seed

# Clear cache
php artisan config:clear
php artisan cache:clear
```

## Environment Variables

```env
# Laravel API (.env)
DB_CONNECTION=mysql
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=password

SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
SESSION_DRIVER=cookie

# Vue App (.env in resources/vue/)
VITE_API_URL=http://localhost:8000/api
```

## Tech Stack

**Frontend:**

- Vue 3 (Composition API)
- Vue Router (SPA routing)
- Pinia (State Management)
- Axios (HTTP client)
- Vite (Build tool)

**Backend:**

- Laravel 11
- Sanctum (API Auth)
- MySQL/MariaDB
- RESTful API

## Additional Packages You Might Want

```bash
# Frontend
npm install date-fns               # Date formatting
npm install @vueuse/core           # Vue utilities
npm install tailwindcss            # CSS framework

# Backend
composer require spatie/laravel-permission  # Roles/Permissions
composer require spatie/laravel-query-builder # Advanced queries
```

## Deployment Considerations

- Use Laravel Forge or Vapor
- Frontend: Netlify, Vercel, or serve via Laravel
- Database: Managed MySQL (DigitalOcean, AWS RDS)
- Consider Redis for caching
- Set up proper CORS in production

## Resources

- [Vue 3 Docs](https://vuejs.org/)
- [Laravel Docs](https://laravel.com/docs)
- [Sanctum Docs](https://laravel.com/docs/sanctum)
- [Pinia Docs](https://pinia.vuejs.org/)
