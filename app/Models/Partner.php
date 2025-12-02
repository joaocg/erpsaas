<?php

namespace App\Models;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Models\Common\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'partners';

    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'document',
        'email',
        'phone',
        'commission_percent',
        'active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        'commission_percent' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'partner_client_links')
            ->withPivot(['linked_at', 'notes', 'company_id'])
            ->withTimestamps();
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function legalCases(): HasMany
    {
        return $this->hasMany(LegalCase::class);
    }
}
