<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FileLifecycleCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_deleting_submission_model_removes_its_private_file(): void
    {
        $submission = Submission::factory()->create([
            'file_path' => 'submissions/1/jawaban.pdf',
        ]);
        Storage::disk('local')->put($submission->file_path, 'submission');

        $submission->delete();

        Storage::disk('local')->assertMissing('submissions/1/jawaban.pdf');
        $this->assertDatabaseMissing('submissions', ['id' => $submission->id]);
    }

    public function test_deleting_material_model_removes_its_private_file(): void
    {
        $material = Material::factory()->file()->create([
            'file_path' => 'materials/modul.pdf',
        ]);
        Storage::disk('local')->put($material->file_path, 'material');

        $material->delete();

        Storage::disk('local')->assertMissing('materials/modul.pdf');
        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
    }

    public function test_deleting_assignment_through_web_cleans_submission_files_before_cascade(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'file_path' => 'submissions/'.$assignment->id.'/jawaban.pdf',
        ]);
        Storage::disk('local')->put($submission->file_path, 'submission');

        $this->actingAs($lecturer)
            ->delete(route('dosen.assignments.destroy', $assignment))
            ->assertRedirect();

        Storage::disk('local')->assertMissing($submission->file_path);
        $this->assertDatabaseMissing('submissions', ['id' => $submission->id]);
        $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
    }

    public function test_deleting_assignment_through_api_cleans_submission_files_before_cascade(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'file_path' => 'submissions/'.$assignment->id.'/jawaban.pdf',
        ]);
        Storage::disk('local')->put($submission->file_path, 'submission');
        Sanctum::actingAs($lecturer);

        $this->deleteJson('/api/v1/assignments/'.$assignment->id)
            ->assertNoContent();

        Storage::disk('local')->assertMissing($submission->file_path);
        $this->assertDatabaseMissing('submissions', ['id' => $submission->id]);
        $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
    }

    public function test_deleting_course_through_web_cleans_material_and_submission_files_before_cascade(): void
    {
        $admin = User::factory()->admin()->create();
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $material = Material::factory()->file()->create([
            'course_id' => $course->id,
            'uploaded_by' => $lecturer->id,
            'file_path' => 'materials/course-handout.pdf',
        ]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'file_path' => 'submissions/'.$assignment->id.'/jawaban.pdf',
        ]);
        Storage::disk('local')->put($material->file_path, 'material');
        Storage::disk('local')->put($submission->file_path, 'submission');

        $this->actingAs($admin)
            ->delete(route('admin.courses.destroy', $course))
            ->assertRedirect();

        Storage::disk('local')->assertMissing($material->file_path);
        Storage::disk('local')->assertMissing($submission->file_path);
        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
        $this->assertDatabaseMissing('submissions', ['id' => $submission->id]);
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_deleting_record_with_missing_physical_file_succeeds(): void
    {
        $submission = Submission::factory()->create([
            'file_path' => 'submissions/missing.pdf',
        ]);

        $submission->delete();

        $this->assertDatabaseMissing('submissions', ['id' => $submission->id]);
    }
}
