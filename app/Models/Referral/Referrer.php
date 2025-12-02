<?php

namespace App\Models\Referral;

use App\Concerns\CompanyOwned;
use App\Models\Employeeship;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referrer extends Model
{
    use CompanyOwned;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employeeship_id',
        'name',
        'document',
        'type',
        'default_commission_percentage',
        'email',
        'phone',
        'whatsapp',
        'notes',
    ];

    protected $casts = [
        'default_commission_percentage' => 'decimal:2',
    ];

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(
            Referrer::class,
            'referrer_relations',
            'parent_id',
            'child_id'
        )->withPivot(['commission_percentage', 'active'])->withTimestamps();
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(
            Referrer::class,
            'referrer_relations',
            'child_id',
            'parent_id'
        )->withPivot(['commission_percentage', 'active'])->withTimestamps();
    }

    public function referralCases(): HasMany
    {
        return $this->hasMany(ReferralCase::class);
    }

    public function referralCommissions(): HasMany
    {
        return $this->hasMany(ReferralCommission::class);
    }

    public function employeeship(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Employeeship::class);
    }
}
