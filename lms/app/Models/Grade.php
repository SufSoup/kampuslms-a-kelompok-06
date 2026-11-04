<?php

namespace App\Models;

use App\Support\DashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(static function (Grade $grade): void {
            DashboardCache::forgetSubmissionLecturer($grade->getRawOriginal('submission_id'));
            DashboardCache::forgetSubmissionLecturer($grade->submission_id);
        });

        static::deleted(static function (Grade $grade): void {
            DashboardCache::forgetSubmissionLecturer($grade->getRawOriginal('submission_id'));
            DashboardCache::forgetSubmissionLecturer($grade->submission_id);
        });
    }

    protected $fillable = [
        'score',
        'feedback',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'graded_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
