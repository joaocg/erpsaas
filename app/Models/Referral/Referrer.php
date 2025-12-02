<?php

namespace App\Models\Referral;

use App\Concerns\CompanyOwned;
use App\Models\Common\Contact;
use App\Models\Company;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wallo\FilamentCompanies\FilamentCompanies;

class Referrer extends Model
{
    use CompanyOwned;

    protected $fillable = [
        'company_id',
        'user_id',
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
            if ($this->user?->name) {
                return $this->user->name;
            }

            return $this->contact?->full_name;
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel());
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
