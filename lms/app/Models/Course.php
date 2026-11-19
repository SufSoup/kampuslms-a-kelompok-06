<?php

namespace App\Models;

use App\Support\DashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Course extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(static function (Course $course): void {
            DashboardCache::forgetLecturerCourses(
                (int) $course->getRawOriginal('lecturer_id')
            );
            DashboardCache::forgetLecturerCourses((int) $course->lecturer_id);
        });

        static::created(static function (): void {
            DashboardCache::forgetAdminStats();
        });

        static::deleted(static function (Course $course): void {
            DashboardCache::forgetAdminStats();
            DashboardCache::forgetLecturerCourses((int) $course->lecturer_id);
        });
    }

    protected $fillable = [
        'code',
        'name',
        'description',
        'sks',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sks' => 'integer',
        ];
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id')->withTrashed();
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(CourseEnrollment::class)
            ->withPivot('enrolled_at')
            ->withTimestamps();
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function submissions(): HasManyThrough
    {
        return $this->hasManyThrough(Submission::class, Assignment::class);
    }

    public function learningResources(): HasMany
    {
        return $this->hasMany(LearningResource::class);
    }
}
