<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Season;

class SeasonController extends Controller
{
    public function index()
    {
        $seasons = Season::query()
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'is_active', 'start_date', 'end_date']);

        return response()->json([
            'data' => $seasons,
        ]);
    }
}
