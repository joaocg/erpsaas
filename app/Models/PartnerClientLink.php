<?php

namespace App\Models;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Models\Common\Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerClientLink extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'partner_client_links';

    protected $fillable = [
        'company_id',
        'partner_id',
        'client_id',
        'linked_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'linked_at' => 'date',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
