<?php

namespace App\Models;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Models\Common\Client;
use App\Observers\ContractObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(ContractObserver::class)]
class Contract extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'client_id',
        'title',
        'total_amount',
        'entry_amount',
        'installment_count',
        'start_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'entry_amount' => 'decimal:2',
        'installment_count' => 'integer',
        'start_date' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function financialMovements(): HasMany
    {
        return $this->hasMany(FinancialMovement::class);
    }
}
