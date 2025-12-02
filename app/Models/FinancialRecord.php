<?php

namespace App\Models;

use App\Concerns\CompanyOwned;
use App\Observers\FinancialRecordObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(FinancialRecordObserver::class)]
class FinancialRecord extends Model
{
    use CompanyOwned;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'category_id',
        'attachment_id',
        'transaction_id',
        'referrer_id',
        'referral_case_id',
        'referral_commission_id',
        'type',
        'amount',
        'currency',
        'occurred_on',
        'description',
        'metadata',
    ];

    protected $casts = [
        'occurred_on' => 'date',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Accounting\Transaction::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(FinancialLedger::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Referral\Referrer::class);
    }

    public function referralCase(): BelongsTo
    {
        return $this->belongsTo(Referral\ReferralCase::class);
    }

    public function referralCommission(): BelongsTo
    {
        return $this->belongsTo(Referral\ReferralCommission::class);
    }
}
