<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            ['name' => 'Eisbären Berlin', 'short_name' => 'EBB', 'logo_url' => '1.svg'],
            ['name' => 'Adler Mannheim', 'short_name' => 'MAN', 'logo_url' => '2.svg'],
            ['name' => 'EHC Red Bull München', 'short_name' => 'MUC', 'logo_url' => '3.svg'],
            ['name' => 'Kölner Haie', 'short_name' => 'KOL', 'logo_url' => '4.svg'],
            ['name' => 'Grizzlys Wolfsburg', 'short_name' => 'WOB', 'logo_url' => '5.svg'],
            ['name' => 'Straubing Tigers', 'short_name' => 'STR', 'logo_url' => '6.svg'],
            ['name' => 'Düsseldorfer EG', 'short_name' => 'DEG', 'logo_url' => '7.svg'],
            ['name' => 'Fischtown Pinguins Bremerhaven', 'short_name' => 'BRE', 'logo_url' => '8.svg'],
            ['name' => 'Iserlohn Roosters', 'short_name' => 'ISE', 'logo_url' => '9.svg'],
            ['name' => 'Augsburger Panther', 'short_name' => 'AUG', 'logo_url' => '10.svg'],
            ['name' => 'Nürnberg Ice Tigers', 'short_name' => 'NIT', 'logo_url' => '11.svg'],
            ['name' => 'ERC Ingolstadt', 'short_name' => 'ING', 'logo_url' => '12.png'],
            ['name' => 'Schwenninger Wild Wings', 'short_name' => 'SWW', 'logo_url' => '13.svg'],
            ['name' => 'Löwen Frankfurt', 'short_name' => 'FRA', 'logo_url' => '14.svg'],
            ['name' => 'Dresdner Eislöwen', 'short_name' => 'DRE', 'logo_url' => '15.png'],
        ];

        foreach ($teams as $team) {
            Team::firstOrCreate(
                ['name' => $team['name']],
                $team
            );
        }
    }
}
