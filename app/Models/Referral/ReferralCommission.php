<?php

namespace App\Models\Referral;

use App\Models\Accounting\Bill;
use App\Models\Accounting\Transaction;
use App\Models\FinancialRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_case_id',
        'referrer_id',
        'bill_id',
        'level',
        'commission_percentage',
        'commission_value',
        'financial_record_id',
        'transaction_id',
        'status',
        'due_date',
        'payment_date',
    ];

    protected $casts = [
        'commission_percentage' => 'decimal:2',
        'commission_value' => 'decimal:2',
        'due_date' => 'date',
        'payment_date' => 'date',
    ];

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

    public function financialRecord(): BelongsTo
    {
        return $this->belongsTo(FinancialRecord::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
