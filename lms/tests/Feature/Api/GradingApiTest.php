<?php

namespace Tests\Feature\Api;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
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

    public function test_course_lecturer_can_grade_submission_created_by_previous_lecturer(): void
    {
        $previousLecturer = User::factory()->dosen()->create();
        $currentLecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create([
            'lecturer_id' => $currentLecturer->id,
        ]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $previousLecturer->id,
        ]);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);

        $this->actingAs($currentLecturer, 'sanctum')
            ->putJson("/api/v1/submissions/{$submission->id}/grade", [
                'score' => 88,
                'feedback' => 'Dinilai pengampu saat ini.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.graded_by', $currentLecturer->id);
    }

    public function test_previous_course_lecturer_cannot_list_or_grade_after_reassignment(): void
    {
        $previousLecturer = User::factory()->dosen()->create();
        $currentLecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create([
            'lecturer_id' => $currentLecturer->id,
        ]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $previousLecturer->id,
        ]);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);

        $this->actingAs($previousLecturer, 'sanctum')
            ->getJson("/api/v1/assignments/{$assignment->id}/submissions")
            ->assertForbidden();
        $this->putJson("/api/v1/submissions/{$submission->id}/grade", [
            'score' => 88,
        ])->assertForbidden();
    }

    public function test_student_cannot_submit_an_assignment_that_is_still_draft(): void
    {
        Storage::fake();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $assignment = Assignment::factory()->draft()->create([
            'course_id' => $course->id,
        ]);

        $this->actingAs($student, 'sanctum')
            ->post("/api/v1/assignments/{$assignment->id}/submissions", [
                'file' => UploadedFile::fake()->create('tugas.pdf', 100, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertForbidden();
    }

    public function test_student_cannot_submit_after_deadline_when_late_submissions_are_disabled(): void
    {
        Storage::fake();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'due_at' => now()->subDay(),
            'allow_late' => false,
        ]);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/assignments/{$assignment->id}/submissions", [
                'file' => UploadedFile::fake()->create('tugas.pdf', 100, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseMissing('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_student_can_submit_at_deadline_when_late_submissions_are_disabled(): void
    {
        Storage::fake();
        $deadline = Carbon::parse('2026-11-13 12:00:00');
        $this->travelTo($deadline);

        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'due_at' => $deadline,
            'allow_late' => false,
        ]);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/assignments/{$assignment->id}/submissions", [
                'file' => UploadedFile::fake()->create('tugas.pdf', 100, 'application/pdf'),
            ])
            ->assertCreated();

        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'is_late' => false,
        ]);
    }

    public function test_student_can_submit_after_deadline_when_late_submissions_are_enabled(): void
    {
        Storage::fake();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'due_at' => now()->subDay(),
            'allow_late' => true,
        ]);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/v1/assignments/{$assignment->id}/submissions", [
                'file' => UploadedFile::fake()->create('tugas.pdf', 100, 'application/pdf'),
            ])
            ->assertCreated();

        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'is_late' => true,
        ]);
    }
}