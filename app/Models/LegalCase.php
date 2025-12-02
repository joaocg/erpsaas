<?php

namespace App\Models;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Models\Accounting\Invoice;
use App\Models\Common\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalCase extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'legal_cases';

    protected $fillable = [
        'company_id',
        'client_id',
        'partner_id',
        'title',
        'description',
        'status',
        'expected_receivable_date',
        'expected_receivable_amount',
        'fee_percent',
        'fee_amount',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'expected_receivable_date' => 'date',
        'expected_receivable_amount' => 'decimal:2',
        'fee_percent' => 'decimal:2',
        'fee_amount' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }
}
