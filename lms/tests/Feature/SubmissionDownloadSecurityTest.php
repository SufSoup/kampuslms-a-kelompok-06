<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionDownloadSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $lecturer;

    private Submission $submission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');

        $this->lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $this->lecturer->id]);
        $this->student = User::factory()->mahasiswa()->create();
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $this->lecturer->id,
        ]);

        $this->submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $this->student->id,
            'file_path' => 'submissions/'.$assignment->id.'/jawaban-acak.pdf',
            'original_name' => 'jawaban.pdf',
            'file_size' => 1_572_864,
        ]);

        Storage::disk('local')->put($this->submission->file_path, 'isi berkas tugas');
    }

    public function test_submission_owner_can_download_their_file(): void
    {
        $this->actingAs($this->student)
            ->get(route('submissions.download', $this->submission))
            ->assertDownload('jawaban.pdf');
    }

    public function test_assignment_lecturer_can_download_submission(): void
    {
        $this->actingAs($this->lecturer)
            ->get(route('submissions.download', $this->submission))
            ->assertDownload('jawaban.pdf');
    }

    public function test_admin_can_download_submission(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('submissions.download', $this->submission))
            ->assertDownload('jawaban.pdf');
    }

    public function test_other_student_cannot_download_submission(): void
    {
        $otherStudent = User::factory()->mahasiswa()->create();

        $this->actingAs($otherStudent)
            ->get(route('submissions.download', $this->submission))
            ->assertForbidden();
    }

    public function test_lecturer_from_another_course_cannot_download_submission(): void
    {
        $otherLecturer = User::factory()->dosen()->create();

        $this->actingAs($otherLecturer)
            ->get(route('submissions.download', $this->submission))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('submissions.download', $this->submission))
            ->assertRedirect(route('login'));
    }

    public function test_missing_physical_file_returns_not_found(): void
    {
        Storage::disk('local')->delete($this->submission->file_path);

        $this->actingAs($this->student)
            ->get(route('submissions.download', $this->submission))
            ->assertNotFound();
    }

    public function test_file_size_accessors_return_human_readable_values(): void
    {
        $this->assertSame('1.5 MB', $this->submission->formatted_file_size);
        $this->assertSame('-', (new Material)->formatted_file_size);
    }
}
