<?php

namespace App\Models;

use App\Models\Concerns\HasFormattedFileSize;
use App\Support\DashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class Submission extends Model
{
    use HasFactory, HasFormattedFileSize;

    protected $fillable = [
        'assignment_id',
        'user_id',
        'file_path',
        'original_name',
        'file_size',
        'note',
        'submitted_at',
        'is_late',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'submitted_at' => 'datetime',
            'is_late' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(static function (Submission $submission): void {
            DashboardCache::forgetAssignmentLecturer($submission->getRawOriginal('assignment_id'));
            DashboardCache::forgetAssignmentLecturer($submission->assignment_id);
        });

        static::deleted(static function (Submission $submission): void {
            DashboardCache::forgetAssignmentLecturer($submission->getRawOriginal('assignment_id'));
            DashboardCache::forgetAssignmentLecturer($submission->assignment_id);
        });

        static::deleting(static function (Submission $submission): void {
            if (
                $submission->file_path
                && Storage::disk('local')->exists($submission->file_path)
                && ! Storage::disk('local')->delete($submission->file_path)
            ) {
                throw new RuntimeException('Berkas submission gagal dihapus dari penyimpanan.');
            }
        });
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grade(): HasOne
    {
        return $this->hasOne(Grade::class);
    }
}
