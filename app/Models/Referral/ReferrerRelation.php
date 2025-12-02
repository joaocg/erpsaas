<?php

namespace App\Models\Referral;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferrerRelation extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'child_id',
        'commission_percentage',
        'active',
    ];

    protected $casts = [
        'commission_percentage' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Referrer::class, 'parent_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Referrer::class, 'child_id');
    }
}
