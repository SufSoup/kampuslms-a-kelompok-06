<?php

namespace App\Models;

use App\Models\Concerns\HasFormattedFileSize;
use App\Support\DashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class Material extends Model
{
    use HasFactory, HasFormattedFileSize;

    protected $fillable = [
        'title',
        'description',
        'type',
        'file_path',
        'external_url',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(static function (Material $material): void {
            DashboardCache::forgetAdminStats();
            DashboardCache::forgetCourseLecturer($material->getRawOriginal('course_id'));
            DashboardCache::forgetCourseLecturer($material->course_id);
        });

        static::deleted(static function (Material $material): void {
            DashboardCache::forgetAdminStats();
            DashboardCache::forgetCourseLecturer($material->course_id);
        });

        static::deleting(static function (Material $material): void {
            if (
                $material->file_path
                && Storage::disk('local')->exists($material->file_path)
                && ! Storage::disk('local')->delete($material->file_path)
            ) {
                throw new RuntimeException('Berkas materi gagal dihapus dari penyimpanan.');
            }
        });
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
