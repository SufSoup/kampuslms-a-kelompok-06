<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use App\Support\DashboardCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardCachingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_caches_statistics_and_reloads_after_relevant_changes(): void
    {
        $admin = User::factory()->admin()->create();
        $deletedUser = User::factory()->mahasiswa()->create();
        $deletedUser->delete();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->assertSame([
            'users' => 1,
            'courses' => 0,
            'materials' => 0,
            'assignments' => 0,
        ], Cache::get(DashboardCache::ADMIN_STATS_KEY));

        $lecturer = User::factory()->dosen()->create();
        $this->assertFalse(Cache::has(DashboardCache::ADMIN_STATS_KEY));
        $this->get(route('dashboard'))->assertOk();
        $this->assertSame(2, Cache::get(DashboardCache::ADMIN_STATS_KEY)['users']);

        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $this->assertFalse(Cache::has(DashboardCache::ADMIN_STATS_KEY));
        $this->get(route('dashboard'))->assertOk();
        $this->assertSame(1, Cache::get(DashboardCache::ADMIN_STATS_KEY)['courses']);

        Material::factory()->create([
            'course_id' => $course->id,
            'uploaded_by' => $lecturer->id,
        ]);
        $this->assertFalse(Cache::has(DashboardCache::ADMIN_STATS_KEY));
        $this->get(route('dashboard'))->assertOk();
        $this->assertSame(1, Cache::get(DashboardCache::ADMIN_STATS_KEY)['materials']);

        Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);
        $this->assertFalse(Cache::has(DashboardCache::ADMIN_STATS_KEY));
        $this->get(route('dashboard'))->assertOk();
        $this->assertSame(1, Cache::get(DashboardCache::ADMIN_STATS_KEY)['assignments']);

        $lecturer->delete();
        $this->assertFalse(Cache::has(DashboardCache::ADMIN_STATS_KEY));
        $deletedUser->restore();
        $this->assertFalse(Cache::has(DashboardCache::ADMIN_STATS_KEY));
    }

    public function test_lecturer_course_cache_is_isolated_and_includes_expected_counts(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $otherLecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create([
            'name' => 'Mata Kuliah Dosen Pertama',
            'lecturer_id' => $lecturer->id,
        ]);
        $additionalCourse = Course::factory()->create([
            'name' => 'Mata Kuliah Dosen Tambahan',
            'lecturer_id' => $lecturer->id,
        ]);
        $otherCourse = Course::factory()->create([
            'name' => 'Mata Kuliah Dosen Kedua',
            'lecturer_id' => $otherLecturer->id,
        ]);
        $otherStudent = User::factory()->mahasiswa()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        Material::factory()->create([
            'course_id' => $course->id,
            'uploaded_by' => $lecturer->id,
        ]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);
        $ungradedSubmission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);
        $gradedSubmission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $otherStudent->id,
        ]);
        Grade::factory()->create([
            'submission_id' => $gradedSubmission->id,
            'graded_by' => $lecturer->id,
        ]);

        $lecturerKey = DashboardCache::lecturerCoursesKey($lecturer->id);
        $otherLecturerKey = DashboardCache::lecturerCoursesKey($otherLecturer->id);

        DB::enableQueryLog();
        $this->actingAs($lecturer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Mata Kuliah Dosen Pertama')
            ->assertSee('Mata Kuliah Dosen Tambahan')
            ->assertDontSee('Mata Kuliah Dosen Kedua')
            ->assertSee('Submission belum dinilai: 1')
            ->assertSee('Submission belum dinilai: 0');
        $courseCountQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains($query['query'], 'ungraded_submissions_count'));
        DB::disableQueryLog();

        $this->assertCount(1, $courseCountQueries);

        $this->actingAs($otherLecturer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Mata Kuliah Dosen Kedua')
            ->assertDontSee('Mata Kuliah Dosen Pertama');

        $this->assertTrue(Cache::has($lecturerKey));
        $this->assertTrue(Cache::has($otherLecturerKey));
        $this->assertNotSame($lecturerKey, $otherLecturerKey);
        $this->assertCount(2, Cache::get($lecturerKey));
        $cachedCourse = Cache::get($lecturerKey)->firstWhere('id', $course->id);
        $this->assertSame(1, $cachedCourse->students_count);
        $this->assertSame(1, $cachedCourse->materials_count);
        $this->assertSame(1, $cachedCourse->assignments_count);
        $this->assertSame(1, $cachedCourse->ungraded_submissions_count);
        $this->assertSame(0, Cache::get($lecturerKey)->firstWhere('id', $additionalCourse->id)->ungraded_submissions_count);
        $this->assertSame($otherCourse->id, Cache::get($otherLecturerKey)->first()->id);
    }

    public function test_lecturer_cache_is_invalidated_by_course_material_and_assignment_changes(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $newLecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $lecturerKey = DashboardCache::lecturerCoursesKey($lecturer->id);
        $newLecturerKey = DashboardCache::lecturerCoursesKey($newLecturer->id);

        $this->actingAs($lecturer)->get(route('dashboard'))->assertOk();
        $course->update(['name' => 'Nama Baru']);
        $this->assertFalse(Cache::has($lecturerKey));

        $this->get(route('dashboard'))->assertOk();
        $material = Material::factory()->create([
            'course_id' => $course->id,
            'uploaded_by' => $lecturer->id,
        ]);
        $this->assertFalse(Cache::has($lecturerKey));

        $this->get(route('dashboard'))->assertOk();
        $material->update(['title' => 'Materi diperbarui']);
        $this->assertFalse(Cache::has($lecturerKey));

        $this->get(route('dashboard'))->assertOk();
        $material->delete();
        $this->assertFalse(Cache::has($lecturerKey));

        $this->get(route('dashboard'))->assertOk();
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);
        $this->assertFalse(Cache::has($lecturerKey));

        $this->get(route('dashboard'))->assertOk();
        $assignment->update(['title' => 'Tugas diperbarui']);
        $this->assertFalse(Cache::has($lecturerKey));

        $this->get(route('dashboard'))->assertOk();
        $assignment->delete();
        $this->assertFalse(Cache::has($lecturerKey));

        $this->get(route('dashboard'))->assertOk();
        $this->actingAs($newLecturer)->get(route('dashboard'))->assertOk();
        $course->lecturer_id = $newLecturer->id;
        $course->save();
        $this->assertFalse(Cache::has($lecturerKey));
        $this->assertFalse(Cache::has($newLecturerKey));

        $this->get(route('dashboard'))->assertOk();
        $course->delete();
        $this->assertFalse(Cache::has($newLecturerKey));
    }

    public function test_submission_lifecycle_and_assignment_course_changes_invalidate_affected_lecturer_caches(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $otherLecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $otherCourse = Course::factory()->create(['lecturer_id' => $otherLecturer->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);
        $otherAssignment = Assignment::factory()->create([
            'course_id' => $otherCourse->id,
            'created_by' => $otherLecturer->id,
        ]);
        $key = DashboardCache::lecturerCoursesKey($lecturer->id);
        $otherKey = DashboardCache::lecturerCoursesKey($otherLecturer->id);

        $this->cacheLecturerDashboard($lecturer);
        $this->cacheLecturerDashboard($otherLecturer);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);
        $this->assertFalse(Cache::has($key));
        $this->assertTrue(Cache::has($otherKey));

        $this->cacheLecturerDashboard($lecturer);
        $submission->update(['note' => 'Jawaban diperbarui']);
        $this->assertFalse(Cache::has($key));

        $this->cacheLecturerDashboard($lecturer);
        $this->cacheLecturerDashboard($otherLecturer);
        $submission->update(['assignment_id' => $otherAssignment->id]);
        $this->assertFalse(Cache::has($key));
        $this->assertFalse(Cache::has($otherKey));

        $this->cacheLecturerDashboard($otherLecturer);
        $submission->delete();
        $this->assertFalse(Cache::has($otherKey));

        $this->cacheLecturerDashboard($lecturer);
        $this->cacheLecturerDashboard($otherLecturer);
        $newSubmission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);
        $this->cacheLecturerDashboard($lecturer);
        $this->cacheLecturerDashboard($otherLecturer);
        $assignment->course_id = $otherCourse->id;
        $assignment->save();
        $this->assertFalse(Cache::has($key));
        $this->assertFalse(Cache::has($otherKey));
        $newSubmission->delete();
    }

    public function test_grade_lifecycle_and_submission_reassignment_invalidate_affected_lecturer_caches(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $otherLecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $otherStudent = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $otherCourse = Course::factory()->create(['lecturer_id' => $otherLecturer->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);
        $otherAssignment = Assignment::factory()->create([
            'course_id' => $otherCourse->id,
            'created_by' => $otherLecturer->id,
        ]);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);
        $otherSubmission = Submission::factory()->create([
            'assignment_id' => $otherAssignment->id,
            'user_id' => $otherStudent->id,
        ]);
        $key = DashboardCache::lecturerCoursesKey($lecturer->id);
        $otherKey = DashboardCache::lecturerCoursesKey($otherLecturer->id);

        $this->cacheLecturerDashboard($lecturer);
        $this->cacheLecturerDashboard($otherLecturer);
        $grade = Grade::factory()->create([
            'submission_id' => $submission->id,
            'graded_by' => $lecturer->id,
        ]);
        $this->assertFalse(Cache::has($key));
        $this->assertTrue(Cache::has($otherKey));

        $this->cacheLecturerDashboard($lecturer);
        $grade->update(['score' => 80]);
        $this->assertFalse(Cache::has($key));

        $this->cacheLecturerDashboard($lecturer);
        $this->cacheLecturerDashboard($otherLecturer);
        $grade->submission_id = $otherSubmission->id;
        $grade->save();
        $this->assertFalse(Cache::has($key));
        $this->assertFalse(Cache::has($otherKey));

        $this->cacheLecturerDashboard($otherLecturer);
        $grade->delete();
        $this->assertFalse(Cache::has($otherKey));
    }

    public function test_enrollment_attach_detach_and_sync_invalidate_lecturer_cache(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $key = DashboardCache::lecturerCoursesKey($lecturer->id);

        $this->actingAs($lecturer)->get(route('dashboard'))->assertOk();
        $this->assertSame(0, Cache::get($key)->first()->students_count);

        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $this->assertFalse(Cache::has($key));

        $this->get(route('dashboard'))->assertOk();
        $course->students()->sync([
            $student->id => ['enrolled_at' => now()->addMinute()],
        ]);
        $this->assertFalse(Cache::has($key));

        $this->get(route('dashboard'))->assertOk();
        $course->students()->detach($student->id);
        $this->assertFalse(Cache::has($key));

        $this->get(route('dashboard'))->assertOk();
        $course->students()->sync([$student->id => ['enrolled_at' => now()]]);
        $this->assertFalse(Cache::has($key));

        $this->get(route('dashboard'))->assertOk();
        $course->students()->sync([]);
        $this->assertFalse(Cache::has($key));
    }

    public function test_student_dashboard_remains_uncached_and_does_not_expose_sensitive_data(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
        ]);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);
        Grade::factory()->create([
            'submission_id' => $submission->id,
            'score' => 98.76,
            'feedback' => 'Feedback-privat-dashboard-unik',
        ]);

        Cache::spy();

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($course->name)
            ->assertDontSee('98.76')
            ->assertDontSee('Feedback-privat-dashboard-unik');

        Cache::shouldNotHaveReceived('remember');
    }

    private function cacheLecturerDashboard(User $lecturer): void
    {
        $this->actingAs($lecturer)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
