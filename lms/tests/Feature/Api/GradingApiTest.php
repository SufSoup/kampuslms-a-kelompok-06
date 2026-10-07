<?php

namespace Tests\Feature\Api;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GradingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrolled_student_can_submit_assignment(): void
    {
        Storage::fake();

        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $course->students()->attach($student->id, [
            'enrolled_at' => now(),
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);

        $file = UploadedFile::fake()->create(
            'tugas.pdf',
            100,
            'application/pdf'
        );

        $response = $this->actingAs($student, 'sanctum')
            ->post(
                "/api/v1/assignments/{$assignment->id}/submissions",
                [
                    'file' => $file,
                    'note' => 'Pengumpulan tugas API.',
                ],
                [
                    'Accept' => 'application/json',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath('data.assignment_id', $assignment->id)
            ->assertJsonPath('data.user_id', $student->id)
            ->assertJsonPath('data.original_name', 'tugas.pdf');

        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'original_name' => 'tugas.pdf',
        ]);
    }

    public function test_student_cannot_submit_assignment_twice(): void
    {
        Storage::fake();

        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $course->students()->attach($student->id, [
            'enrolled_at' => now(),
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);

        Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);

        $file = UploadedFile::fake()->create(
            'tugas-kedua.pdf',
            100,
            'application/pdf'
        );

        $response = $this->actingAs($student, 'sanctum')
            ->post(
                "/api/v1/assignments/{$assignment->id}/submissions",
                [
                    'file' => $file,
                ],
                [
                    'Accept' => 'application/json',
                ]
            );

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Data yang diberikan tidak valid.',
            ]);
    }

    public function test_lecturer_can_list_submissions_for_their_assignment(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);

        Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);

        $response = $this->actingAs($lecturer, 'sanctum')
            ->getJson("/api/v1/assignments/{$assignment->id}/submissions");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_other_lecturer_cannot_list_submissions(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $otherLecturer = User::factory()->dosen()->create();

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);

        $response = $this->actingAs($otherLecturer, 'sanctum')
            ->getJson("/api/v1/assignments/{$assignment->id}/submissions");

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ]);
    }

    public function test_lecturer_can_create_grade(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);

        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);

        $response = $this->actingAs($lecturer, 'sanctum')
            ->putJson(
                "/api/v1/submissions/{$submission->id}/grade",
                [
                    'score' => 85,
                    'feedback' => 'Hasil tugas sudah baik.',
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath('data.submission_id', $submission->id)
            ->assertJsonPath('data.graded_by', $lecturer->id)
            ->assertJsonPath('data.score', '85.00');

        $this->assertDatabaseHas('grades', [
            'submission_id' => $submission->id,
            'graded_by' => $lecturer->id,
            'score' => 85,
        ]);
    }

    public function test_lecturer_can_update_existing_grade(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);

        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);

        $grade = Grade::factory()->create([
            'submission_id' => $submission->id,
            'graded_by' => $lecturer->id,
            'score' => 70,
        ]);

        $response = $this->actingAs($lecturer, 'sanctum')
            ->putJson(
                "/api/v1/submissions/{$submission->id}/grade",
                [
                    'score' => 90,
                    'feedback' => 'Nilai diperbarui.',
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $grade->id)
            ->assertJsonPath('data.score', '90.00');

        $this->assertDatabaseHas('grades', [
            'id' => $grade->id,
            'submission_id' => $submission->id,
            'score' => 90,
        ]);
    }

    public function test_other_lecturer_cannot_grade_submission(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $otherLecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);

        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);

        $response = $this->actingAs($otherLecturer, 'sanctum')
            ->putJson(
                "/api/v1/submissions/{$submission->id}/grade",
                [
                    'score' => 90,
                    'feedback' => 'Percobaan IDOR.',
                ]
            );

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ]);
    }

    public function test_grade_score_cannot_exceed_100(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);

        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);

        $response = $this->actingAs($lecturer, 'sanctum')
            ->putJson(
                "/api/v1/submissions/{$submission->id}/grade",
                [
                    'score' => 150,
                    'feedback' => 'Nilai tidak valid.',
                ]
            );

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' => 'Data yang diberikan tidak valid.',
            ])
            ->assertJsonValidationErrors(['score']);
    }
}