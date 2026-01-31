<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            ['name' => 'Eisbären Berlin', 'short_name' => 'EBB'],
            ['name' => 'Adler Mannheim', 'short_name' => 'MAN'],
            ['name' => 'EHC Red Bull München', 'short_name' => 'MUC'],
            ['name' => 'Kölner Haie', 'short_name' => 'KOL'],
            ['name' => 'Grizzlys Wolfsburg', 'short_name' => 'WOB'],
            ['name' => 'Straubing Tigers', 'short_name' => 'STR'],
            ['name' => 'Düsseldorfer EG', 'short_name' => 'DEG'],
            ['name' => 'Fischtown Pinguins Bremerhaven', 'short_name' => 'BRE'],
            ['name' => 'Iserlohn Roosters', 'short_name' => 'ISE'],
            ['name' => 'Augsburger Panther', 'short_name' => 'AUG'],
            ['name' => 'Nürnberg Ice Tigers', 'short_name' => 'NIT'],
            ['name' => 'ERC Ingolstadt', 'short_name' => 'ING'],
            ['name' => 'Schwenninger Wild Wings', 'short_name' => 'SWW'],
            ['name' => 'Löwen Frankfurt', 'short_name' => 'FRA'],
        ];

        foreach ($teams as $team) {
            Team::firstOrCreate(
                ['name' => $team['name']],
                $team
            );
        }
    }
}
