<?php

namespace App\Models;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Observers\ServiceActivityObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(ServiceActivityObserver::class)]
class ServiceActivity extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $fillable = [
        'service_id',
        'company_id',
        'name',
        'type',
        'cost',
        'activity_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'activity_date' => 'date',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
