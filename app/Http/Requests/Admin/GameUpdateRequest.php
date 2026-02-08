<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GameUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Gate läuft über middleware('admin')
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::in(['scheduled', 'live', 'finished'])],

            'kickoff_at' => ['sometimes', 'nullable', 'date'],

            'is_home' => ['sometimes', 'boolean'],
            'is_playoff' => ['sometimes', 'boolean'],
            'is_derby' => ['sometimes', 'boolean'],

            'eisbaeren_goals' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'opponent_goals' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Ungültiger Status. Erlaubt: scheduled, live, finished.',
            'kickoff_at.date' => 'Spielbeginn muss ein gültiges Datum sein.',
            'is_home.boolean' => 'Heimspiel muss true/false sein.',
            'is_playoff.boolean' => 'Playoff muss true/false sein.',
            'is_derby.boolean' => 'Derby muss true/false sein.',
            'eisbaeren_goals.integer' => 'Eisbären Tore müssen eine ganze Zahl sein.',
            'opponent_goals.integer' => 'Gegner Tore müssen eine ganze Zahl sein.',
        ];
    }
}
