<?php

namespace App\Models;

use App\Concerns\CompanyOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use CompanyOwned;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'path',
        'original_name',
        'mime',
        'size',
        'source',
        'gemini_status',
        'gemini_summary',
        'gemini_topics',
        'gemini_amount',
        'gemini_currency',
        'gemini_detected_type',
        'raw_payload',
        'processed_at',
    ];

    protected $casts = [
        'gemini_topics' => 'array',
        'raw_payload' => 'array',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function financialRecords(): HasMany
    {
        return $this->hasMany(FinancialRecord::class);
    }

    public function medicalAppointments(): HasMany
    {
        return $this->hasMany(MedicalAppointment::class);
    }

    public function medicalExams(): HasMany
    {
        return $this->hasMany(MedicalExam::class);
    }
}
