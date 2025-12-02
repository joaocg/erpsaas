<?php

namespace App\Models;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Enums\CommissionStatus;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Invoice;
use App\Models\Common\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'commissions';

    protected $fillable = [
        'company_id',
        'partner_id',
        'client_id',
        'legal_case_id',
        'invoice_id',
        'bill_id',
        'base_amount',
        'commission_percent',
        'commission_amount',
        'status',
        'due_date',
        'paid_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => CommissionStatus::class,
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'commission_percent' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'base_amount' => 'decimal:2',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }
}
