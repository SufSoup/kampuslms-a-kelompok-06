<?php

namespace Tests\Feature;

use App\Events\GradeGiven;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\GradeReleasedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GradingWebTest extends TestCase
{
    use RefreshDatabase;

    private User $lecturer;

    private User $student;

    private Course $course;

    private Assignment $assignment;

    private Submission $submission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lecturer = User::factory()->dosen()->create();
        $this->student = User::factory()->mahasiswa()->create();
        $this->course = Course::factory()->create([
            'lecturer_id' => $this->lecturer->id,
        ]);
        $this->course->students()->attach($this->student->id, [
            'enrolled_at' => now(),
        ]);
        $this->assignment = Assignment::factory()->create([
            'course_id' => $this->course->id,
            'created_by' => $this->lecturer->id,
            'max_score' => 80,
            'status' => 'published',
        ]);
        $this->submission = Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $this->student->id,
        ]);
    }

    public function test_authorized_lecturer_can_open_the_grading_form(): void
    {
        $this->actingAs($this->lecturer)
            ->get(route('dosen.submissions.grade.edit', $this->submission))
            ->assertOk()
            ->assertSee($this->student->name)
            ->assertSee('max="80"', false)
            ->assertSee('name="feedback"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_authorized_lecturer_can_save_a_valid_grade_and_feedback(): void
    {
        Event::fake([GradeGiven::class]);

        $this->actingAs($this->lecturer)
            ->post(route('dosen.submissions.grade.store', $this->submission), [
                'score' => '72.50',
                'feedback' => 'Analisis sudah baik.',
            ])
            ->assertRedirect(route('dosen.assignments.submissions.index', $this->assignment))
            ->assertSessionHas('success', 'Nilai berhasil disimpan.');

        $grade = Grade::where('submission_id', $this->submission->id)->firstOrFail();
        $this->assertSame('72.50', $grade->score);
        $this->assertSame('Analisis sudah baik.', $grade->feedback);
        $this->assertSame($this->lecturer->id, $grade->graded_by);
        $this->assertNotNull($grade->graded_at);
        Event::assertDispatched(GradeGiven::class, fn (GradeGiven $event) => $event->grade->is($grade));
    }

    public function test_authorized_lecturer_can_update_an_existing_grade(): void
    {
        $grade = Grade::factory()->create([
            'submission_id' => $this->submission->id,
            'graded_by' => $this->lecturer->id,
            'score' => 60,
        ]);

        $this->actingAs($this->lecturer)
            ->post(route('dosen.submissions.grade.store', $this->submission), [
                'score' => 75,
                'feedback' => 'Nilai diperbarui.',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('grades', 1);
        $this->assertDatabaseHas('grades', [
            'id' => $grade->id,
            'submission_id' => $this->submission->id,
            'score' => 75,
            'feedback' => 'Nilai diperbarui.',
        ]);
    }

    public function test_scores_outside_zero_to_assignment_maximum_are_rejected(): void
    {
        foreach (['-0.01', '80.01'] as $score) {
            $this->actingAs($this->lecturer)
                ->post(route('dosen.submissions.grade.store', $this->submission), [
                    'score' => $score,
                    'feedback' => 'Validasi server harus menolak nilai ini.',
                ])
                ->assertSessionHasErrors('score');
        }

        $this->assertDatabaseMissing('grades', [
            'submission_id' => $this->submission->id,
        ]);
    }

    public function test_existing_api_also_rejects_scores_above_assignment_maximum(): void
    {
        $this->actingAs($this->lecturer, 'sanctum')
            ->putJson("/api/v1/submissions/{$this->submission->id}/grade", [
                'score' => '80.01',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('score');

        $this->assertDatabaseMissing('grades', [
            'submission_id' => $this->submission->id,
        ]);
    }

    public function test_lecturer_from_another_course_cannot_grade_the_submission(): void
    {
        $otherLecturer = User::factory()->dosen()->create();

        $this->actingAs($otherLecturer)
            ->get(route('dosen.submissions.grade.edit', $this->submission))
            ->assertForbidden();

        $this->actingAs($otherLecturer)
            ->post(route('dosen.submissions.grade.store', $this->submission), [
                'score' => 60,
                'feedback' => 'Tidak berwenang.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('grades', [
            'submission_id' => $this->submission->id,
        ]);
    }

    public function test_student_can_see_their_own_grade_and_feedback_on_assignment_detail(): void
    {
        Grade::factory()->create([
            'submission_id' => $this->submission->id,
            'graded_by' => $this->lecturer->id,
            'score' => 72.5,
            'feedback' => 'Pembahasan lengkap.',
        ]);

        $this->actingAs($this->student)
            ->get(route('mahasiswa.assignments.show', $this->assignment))
            ->assertOk()
            ->assertSee('72.50')
            ->assertSee('80')
            ->assertSee('Pembahasan lengkap.')
            ->assertSee($this->lecturer->name)
            ->assertSee('Tanggal penilaian');
    }

    public function test_student_cannot_see_another_students_grade_on_assignment_detail(): void
    {
        $otherStudent = User::factory()->mahasiswa()->create();
        $otherSubmission = Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $otherStudent->id,
        ]);
        Grade::factory()->create([
            'submission_id' => $otherSubmission->id,
            'graded_by' => $this->lecturer->id,
            'score' => 61.25,
            'feedback' => 'Feedback privat mahasiswa lain.',
        ]);

        $this->actingAs($this->student)
            ->get(route('mahasiswa.assignments.show', $this->assignment))
            ->assertOk()
            ->assertDontSee('61.25')
            ->assertDontSee('Feedback privat mahasiswa lain.');

        $this->actingAs($this->student)
            ->get(route('mahasiswa.submissions.show', $otherSubmission))
            ->assertForbidden();
    }

    public function test_ungraded_submission_is_displayed_without_an_error(): void
    {
        $this->actingAs($this->student)
            ->get(route('mahasiswa.assignments.show', $this->assignment))
            ->assertOk()
            ->assertSee('Belum dinilai');

        $this->actingAs($this->student)
            ->get(route('mahasiswa.grades.index'))
            ->assertOk()
            ->assertSee($this->assignment->title)
            ->assertSee('Belum dinilai');
    }

    public function test_guest_cannot_access_protected_grading_or_grade_pages(): void
    {
        $this->get(route('dosen.submissions.grade.edit', $this->submission))
            ->assertRedirect(route('login'));

        $this->post(route('dosen.submissions.grade.store', $this->submission), [
            'score' => 50,
        ])->assertRedirect(route('login'));

        $this->get(route('mahasiswa.grades.index'))
            ->assertRedirect(route('login'));
    }

    public function test_web_grading_dispatches_sanitized_notification_to_submission_owner(): void
    {
        $otherStudent = User::factory()->mahasiswa()->create();

        $this->actingAs($this->lecturer)
            ->post(route('dosen.submissions.grade.store', $this->submission), [
                'score' => 70,
                'feedback' => 'Feedback ini tidak boleh masuk payload notifikasi.',
            ])
            ->assertRedirect();

        $notification = $this->student->notifications()
            ->where('type', GradeReleasedNotification::class)
            ->firstOrFail();

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $otherStudent->id,
            'notifiable_type' => User::class,
            'type' => GradeReleasedNotification::class,
        ]);
        $this->assertArrayNotHasKey('score', $notification->data);
        $this->assertArrayNotHasKey('feedback', $notification->data);
    }

    public function test_course_grade_report_is_private_to_the_assigned_lecturer(): void
    {
        $this->actingAs($this->lecturer)
            ->get(route('dosen.courses.grades.index', $this->course))
            ->assertOk()
            ->assertSee($this->student->name)
            ->assertSee($this->assignment->title)
            ->assertSee('Belum dinilai');

        $otherLecturer = User::factory()->dosen()->create();
        $this->actingAs($otherLecturer)
            ->get(route('dosen.courses.grades.index', $this->course))
            ->assertForbidden();
    }

    public function test_student_grade_report_only_lists_the_authenticated_students_submissions(): void
    {
        $ownGrade = Grade::factory()->create([
            'submission_id' => $this->submission->id,
            'graded_by' => $this->lecturer->id,
            'score' => 76,
            'feedback' => 'Feedback milik saya.',
        ]);
        $otherStudent = User::factory()->mahasiswa()->create();
        $otherSubmission = Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $otherStudent->id,
        ]);
        Grade::factory()->create([
            'submission_id' => $otherSubmission->id,
            'graded_by' => $this->lecturer->id,
            'score' => 12,
            'feedback' => 'Feedback rahasia orang lain.',
        ]);

        $this->actingAs($this->student)
            ->get(route('mahasiswa.grades.index'))
            ->assertOk()
            ->assertSee($this->assignment->title)
            ->assertSee($ownGrade->feedback)
            ->assertDontSee('Feedback rahasia orang lain.')
            ->assertDontSee('12.00');
    }
}
