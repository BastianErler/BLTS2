<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'Adler Mannheim',
                'EHC Red Bull München',
                'Kölner Haie',
                'Grizzlys Wolfsburg',
                'Straubing Tigers',
                'Düsseldorfer EG',
                'Fischtown Pinguins Bremerhaven',
                'Iserlohn Roosters',
                'Augsburger Panther',
                'Nürnberg Ice Tigers',
                'ERC Ingolstadt',
                'Schwenninger Wild Wings',
                'Löwen Frankfurt',
            ]),
            'short_name' => $this->faker->lexify('???'),
            'logo_url' => null,
        ];
    }
}
