<?php

namespace App\Models;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Observers\ServiceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(ServiceObserver::class)]
class Service extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'title',
        'base_price',
        'description',
        'activity_cost',
        'total_cost',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'activity_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function activities(): HasMany
    {
        return $this->hasMany(ServiceActivity::class);
    }

    public function refreshTotals(): void
    {
        $activityCost = $this->activities()->sum('cost');
        $this->updateQuietly([
            'activity_cost' => $activityCost,
            'total_cost' => $this->base_price + $activityCost,
        ]);
    }
}
