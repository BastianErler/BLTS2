<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Team::truncate();

        $teams = [
            ['id' => 1,  'name' => 'Augsburger Panther',        'short_name' => 'AUG', 'logo_url' => 'team_AUG.svg'],
            ['id' => 2,  'name' => 'Fischtown Pinguins',        'short_name' => 'BHV', 'logo_url' => 'team_BHV.svg'],
            ['id' => 3,  'name' => 'Düsseldorfer EG',           'short_name' => 'DEG', 'logo_url' => 'team_DEG.svg'],
            ['id' => 4,  'name' => 'Eisbären Berlin',           'short_name' => 'EBB', 'logo_url' => 'team_EBB.svg'],
            ['id' => 5,  'name' => 'Iserlohn Roosters',         'short_name' => 'IEC', 'logo_url' => 'team_IEC.svg'],
            ['id' => 6,  'name' => 'ERC Ingolstadt',            'short_name' => 'ING', 'logo_url' => 'team_ING.png'],
            ['id' => 7,  'name' => 'Kölner Haie',               'short_name' => 'KEC', 'logo_url' => 'team_KEC.svg'],
            ['id' => 8,  'name' => 'Krefeld Pinguine',          'short_name' => 'KEV', 'logo_url' => 'team_KEV.svg'],
            ['id' => 9,  'name' => 'Adler Mannheim',            'short_name' => 'MAN', 'logo_url' => 'team_MAN.svg'],
            ['id' => 10, 'name' => 'EHC Red Bull München',      'short_name' => 'MUC', 'logo_url' => 'team_MUC.svg'],
            ['id' => 11, 'name' => 'Nürnberg Ice Tigers',       'short_name' => 'NIT', 'logo_url' => 'team_NIT.svg'],
            ['id' => 12, 'name' => 'Straubing Tigers',          'short_name' => 'STR', 'logo_url' => 'team_STR.svg'],
            ['id' => 13, 'name' => 'Schwenninger Wild Wings',   'short_name' => 'SWW', 'logo_url' => 'team_SWW.svg'],
            ['id' => 14, 'name' => 'Grizzlys Wolfsburg',        'short_name' => 'WOB', 'logo_url' => 'team_WOB.svg'],
            ['id' => 15, 'name' => 'Bietigheim Steelers',       'short_name' => 'SCB', 'logo_url' => 'team_SCB.svg'],
            ['id' => 16, 'name' => 'Löwen Frankfurt',           'short_name' => 'LOW', 'logo_url' => 'team_LOW.svg'],
            ['id' => 17, 'name' => 'Dresdner Eislöwen',         'short_name' => 'ELN', 'logo_url' => 'team_ELN.png'],
        ];

        foreach ($teams as $team) {
            Team::create($team);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
