<?php

namespace App\Models\Referral;

use App\Concerns\CompanyOwned;
use App\Models\Accounting\Invoice;
use App\Models\Common\Client;
use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wallo\FilamentCompanies\FilamentCompanies;

class ReferralCase extends Model
{
    use CompanyOwned;

    protected $fillable = [
        'company_id',
        'referrer_id',
        'client_id',
        'responsible_user_id',
        'invoice_id',
        'title',
        'description',
        'estimated_value',
        'opened_at',
        'closed_at',
        'status',
    ];

    protected $casts = [
        'opened_at' => 'date',
        'closed_at' => 'date',
        'estimated_value' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Referrer::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'responsible_user_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(ReferralCommission::class);
    }
}
