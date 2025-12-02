<?php

namespace App\Models\Referral;

use App\Concerns\CompanyOwned;
use App\Models\Accounting\Bill;
use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralCommission extends Model
{
    use CompanyOwned;

    protected $fillable = [
        'company_id',
        'referral_case_id',
        'referrer_id',
        'bill_id',
        'amount',
        'rate',
        'due_date',
        'settled_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'rate' => 'decimal:2',
        'due_date' => 'date',
        'settled_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function referralCase(): BelongsTo
    {
        return $this->belongsTo(ReferralCase::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Referrer::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
