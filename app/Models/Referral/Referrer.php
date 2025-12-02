<?php

namespace App\Models\Referral;

use App\Concerns\CompanyOwned;
use App\Models\Common\Contact;
use App\Models\Company;
use App\Models\Employeeship;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referrer extends Model
{
    use CompanyOwned;

    protected $fillable = [
        'company_id',
        'employeeship_id',
        'contact_id',
        'parent_id',
        'default_commission_rate',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'default_commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected function name(): Attribute
    {
        return Attribute::get(function () {
            if ($this->employeeship?->name) {
                return $this->employeeship->name;
            }

            return $this->contact?->full_name;
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employeeship(): BelongsTo
    {
        return $this->belongsTo(Employeeship::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(ReferralCase::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(ReferralCommission::class);
    }
}
