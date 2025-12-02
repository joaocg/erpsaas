<?php

namespace App\Models\Referral;

use App\Concerns\CompanyOwned;
use App\Models\Accounting\Invoice;
use App\Models\Common\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralCase extends Model
{
    use CompanyOwned;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'referrer_id',
        'client_id',
        'invoice_id',
        'office_lawyer_id',
        'description',
        'case_value',
        'status',
        'contract_date',
        'expected_payment_date',
    ];

    protected $casts = [
        'case_value' => 'decimal:2',
        'contract_date' => 'date',
        'expected_payment_date' => 'date',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Referrer::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function officeLawyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'office_lawyer_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(ReferralCommission::class);
    }
}
