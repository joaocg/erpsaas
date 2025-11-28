<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'attachment_id',
        'exam_type',
        'lab_name',
        'occurred_on',
        'status',
        'notes',
        'results_json',
    ];

    protected $casts = [
        'occurred_on' => 'date',
        'results_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class);
    }
}
