<?php

namespace App\Support;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Support\Facades\Cache;

class DashboardCache
{
    public const ADMIN_STATS_KEY = 'dashboard:admin:stats';

    public const TTL_MINUTES = 10;

    public static function lecturerCoursesKey(int $lecturerId): string
    {
        return "dashboard:lecturer:{$lecturerId}:courses";
    }

    public static function forgetAdminStats(): void
    {
        Cache::forget(self::ADMIN_STATS_KEY);
    }

    public static function forgetLecturerCourses(?int $lecturerId): void
    {
        if ($lecturerId !== null) {
            Cache::forget(self::lecturerCoursesKey($lecturerId));
        }
    }

    public static function forgetCourseLecturer(int|string|null $courseId): void
    {
        if ($courseId === null) {
            return;
        }

        $lecturerId = Course::query()->whereKey($courseId)->value('lecturer_id');

        self::forgetLecturerCourses($lecturerId === null ? null : (int) $lecturerId);
    }

    public static function forgetAssignmentLecturer(int|string|null $assignmentId): void
    {
        if ($assignmentId === null) {
            return;
        }

        $courseId = Assignment::query()->whereKey($assignmentId)->value('course_id');

        self::forgetCourseLecturer($courseId);
    }

    public static function forgetSubmissionLecturer(int|string|null $submissionId): void
    {
        if ($submissionId === null) {
            return;
        }

        $lecturerId = Course::query()
            ->whereHas('assignments.submissions', fn ($query) => $query->whereKey($submissionId))
            ->value('lecturer_id');

        self::forgetLecturerCourses($lecturerId === null ? null : (int) $lecturerId);
    }
}
