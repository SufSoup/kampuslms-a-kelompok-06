<?php

namespace App\Models;

use App\Support\DashboardCache;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CourseEnrollment extends Pivot
{
    protected $table = 'course_user';

    protected static function booted(): void
    {
        static::saved(static function (CourseEnrollment $enrollment): void {
            DashboardCache::forgetCourseLecturer($enrollment->course_id);
        });

        static::deleted(static function (CourseEnrollment $enrollment): void {
            DashboardCache::forgetCourseLecturer($enrollment->course_id);
        });
    }
}
