<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'creator_id',
        'type',
        'amount',
        'description',
        'bet_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function bet(): BelongsTo
    {
        return $this->belongsTo(Bet::class);
    }
}
