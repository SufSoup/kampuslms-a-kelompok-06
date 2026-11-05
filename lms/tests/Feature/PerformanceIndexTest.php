<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerformanceIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_migration_is_present_and_rollback_is_reversible(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
            'status' => 'active',
        ]);

        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'status' => 'published',
        ]);

        Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'submitted_at' => now(),
        ]);

        $courseIndexes = collect(DB::select("PRAGMA index_list('courses')"))->pluck('name');
        $assignmentIndexes = collect(DB::select("PRAGMA index_list('assignments')"))->pluck('name');
        $submissionIndexes = collect(DB::select("PRAGMA index_list('submissions')"))->pluck('name');

        $this->assertTrue($courseIndexes->contains('courses_lecturer_id_status_index'));
        $this->assertTrue($assignmentIndexes->contains('assignments_course_id_status_index'));
        $this->assertTrue($assignmentIndexes->contains('assignments_status_index'));
        $this->assertTrue($submissionIndexes->contains('submissions_assignment_id_submitted_at_index'));

        $this->artisan('migrate:rollback', ['--step' => 1])->assertSuccessful();
        $this->assertTrue(Schema::hasTable('courses'));
        $this->assertTrue(Schema::hasTable('assignments'));
        $this->assertTrue(Schema::hasTable('submissions'));

        $this->assertFalse(
            collect(DB::select("PRAGMA index_list('courses')"))
                ->pluck('name')
                ->contains('courses_lecturer_id_status_index')
        );
        $this->assertFalse(
            collect(DB::select("PRAGMA index_list('assignments')"))
                ->pluck('name')
                ->contains('assignments_course_id_status_index')
        );
        $this->assertFalse(
            collect(DB::select("PRAGMA index_list('assignments')"))
                ->pluck('name')
                ->contains('assignments_status_index')
        );
        $this->assertFalse(
            collect(DB::select("PRAGMA index_list('submissions')"))
                ->pluck('name')
                ->contains('submissions_assignment_id_submitted_at_index')
        );

        $this->artisan('migrate')->assertSuccessful();
    }

    public function test_query_plans_select_added_indexes_after_unindexed_comparison(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $firstCourseId = null;
        $firstAssignmentId = null;

        foreach (range(1, 40) as $index) {
            $course = Course::factory()->create([
                'lecturer_id' => $lecturer->id,
                'status' => 'active',
            ]);

            $assignment = Assignment::factory()->create([
                'course_id' => $course->id,
                'created_by' => $lecturer->id,
                'status' => 'published',
            ]);
            $firstCourseId ??= $course->id;
            $firstAssignmentId ??= $assignment->id;

            foreach (range(1, 10) as $submissionIndex) {
                Submission::factory()->create([
                    'assignment_id' => $assignment->id,
                    'user_id' => User::factory()->mahasiswa()->create()->id,
                    'submitted_at' => now()->subMinutes($submissionIndex),
                ]);
            }
        }

        DB::statement('DROP INDEX courses_lecturer_id_status_index');
        DB::statement('DROP INDEX assignments_course_id_status_index');
        DB::statement('DROP INDEX submissions_assignment_id_submitted_at_index');

        $coursePlanBefore = $this->queryPlan(
            'SELECT * FROM courses WHERE lecturer_id = ? AND status = ?',
            [$lecturer->id, 'active']
        );
        $assignmentPlanBefore = $this->queryPlan(
            'SELECT * FROM assignments WHERE course_id = ? AND status = ?',
            [$firstCourseId, 'published']
        );
        $submissionPlanBefore = $this->queryPlan(
            'SELECT * FROM submissions WHERE assignment_id = ? ORDER BY submitted_at DESC LIMIT 10',
            [$firstAssignmentId]
        );

        DB::statement('CREATE INDEX courses_lecturer_id_status_index ON courses (lecturer_id, status)');
        DB::statement('CREATE INDEX assignments_course_id_status_index ON assignments (course_id, status)');
        DB::statement('CREATE INDEX submissions_assignment_id_submitted_at_index ON submissions (assignment_id, submitted_at)');

        $coursePlanAfter = $this->queryPlan(
            'SELECT * FROM courses WHERE lecturer_id = ? AND status = ?',
            [$lecturer->id, 'active']
        );
        $assignmentPlanAfter = $this->queryPlan(
            'SELECT * FROM assignments WHERE course_id = ? AND status = ?',
            [$firstCourseId, 'published']
        );
        $submissionPlanAfter = $this->queryPlan(
            'SELECT * FROM submissions WHERE assignment_id = ? ORDER BY submitted_at DESC LIMIT 10',
            [$firstAssignmentId]
        );

        $this->assertStringNotContainsString('courses_lecturer_id_status_index', $coursePlanBefore);
        $this->assertStringNotContainsString('assignments_course_id_status_index', $assignmentPlanBefore);
        $this->assertStringNotContainsString('submissions_assignment_id_submitted_at_index', $submissionPlanBefore);

        $this->assertStringContainsString('courses_lecturer_id_status_index', $coursePlanAfter);
        $this->assertStringContainsString('assignments_course_id_status_index', $assignmentPlanAfter);
        $this->assertStringContainsString('submissions_assignment_id_submitted_at_index', $submissionPlanAfter);
    }

    /**
     * @param array<int, mixed> $bindings
     */
    private function queryPlan(string $query, array $bindings): string
    {
        return collect(DB::select("EXPLAIN QUERY PLAN {$query}", $bindings))
            ->pluck('detail')
            ->implode(' ');
    }

}
