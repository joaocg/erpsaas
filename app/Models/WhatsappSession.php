<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone_e164',
        'last_intent',
        'state_payload',
        'last_message_at',
    ];

    protected $casts = [
        'state_payload' => 'array',
        'last_message_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
