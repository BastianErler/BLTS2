<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // version optional (falls du später migrierst)
            'v' => ['sometimes', 'integer', 'min:1', 'max:100'],

            'push_enabled' => ['sometimes', 'boolean'],

            'remind_before_deadline' => ['sometimes', 'boolean'],
            'remind_before_deadline_minutes' => ['sometimes', 'integer', 'in:30,60,120'],

            'remind_on_game_start_if_no_bet' => ['sometimes', 'boolean'],

            'notify_on_bet_result' => ['sometimes', 'boolean'],

            'notify_on_rank_change' => ['sometimes', 'boolean'],
            'rank_change_threshold' => ['sometimes', 'integer', 'in:1,3,5'],
        ];
    }
}
