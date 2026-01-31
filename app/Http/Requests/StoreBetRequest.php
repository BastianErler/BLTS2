<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'game_id' => 'required|exists:games,id',
            'eisbaeren_goals' => 'required|integer|min:0|max:20',
            'opponent_goals' => 'required|integer|min:0|max:20',
            'joker_type' => 'nullable|in:safety,precision,comeback,insurance',
        ];
    }

    public function messages(): array
    {
        return [
            'eisbaeren_goals.required' => 'Bitte Eisbären-Tore eingeben',
            'opponent_goals.required' => 'Bitte Gegner-Tore eingeben',
            'eisbaeren_goals.min' => 'Tore können nicht negativ sein',
            'opponent_goals.min' => 'Tore können nicht negativ sein',
            'eisbaeren_goals.max' => 'Maximal 20 Tore möglich',
            'opponent_goals.max' => 'Maximal 20 Tore möglich',
            'joker_type.in' => 'Ungültiger Joker-Typ',
        ];
    }
}
