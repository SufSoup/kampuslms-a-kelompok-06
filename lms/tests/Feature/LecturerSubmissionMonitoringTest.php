<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LecturerSubmissionMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private User $lecturer;

    private Course $course;

    private Assignment $assignment;

    private User $submittedStudent;

    private User $unsubmittedStudent;

    private Submission $submission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lecturer = User::factory()->dosen()->create();
        $this->course = Course::factory()->create([
            'lecturer_id' => $this->lecturer->id,
            'name' => 'Pemrograman Web',
        ]);
        $this->assignment = Assignment::factory()->create([
            'course_id' => $this->course->id,
            'created_by' => $this->lecturer->id,
            'title' => 'Tugas Minggu 9',
        ]);

        $this->submittedStudent = User::factory()->mahasiswa()->create([
            'name' => 'Mahasiswa Mengumpulkan',
        ]);
        $this->unsubmittedStudent = User::factory()->mahasiswa()->create([
            'name' => 'Mahasiswa Belum Mengumpulkan',
        ]);
        $this->course->students()->attach([
            $this->submittedStudent->id => ['enrolled_at' => now()],
            $this->unsubmittedStudent->id => ['enrolled_at' => now()],
        ]);

        $this->submission = Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $this->submittedStudent->id,
            'original_name' => 'laporan-minggu-9.pdf',
            'file_size' => 1_572_864,
            'submitted_at' => now(),
            'is_late' => true,
        ]);
    }

    public function test_assignment_lecturer_can_open_monitoring_page(): void
    {
        $this->actingAs($this->lecturer)
            ->get(route('dosen.assignments.submissions.index', $this->assignment))
            ->assertOk()
            ->assertSee('Tugas Minggu 9')
            ->assertSee('Pemrograman Web');
    }

    public function test_monitoring_shows_submission_details_and_existing_links(): void
    {
        $this->actingAs($this->lecturer)
            ->get(route('dosen.assignments.submissions.index', $this->assignment))
            ->assertOk()
            ->assertSee('Mahasiswa Mengumpulkan')
            ->assertSee('laporan-minggu-9.pdf')
            ->assertSee('1.5 MB')
            ->assertSee('Terlambat')
            ->assertSee(route('submissions.download', $this->submission), false)
            ->assertSee(route('dosen.submissions.show', $this->submission), false);
    }

    public function test_monitoring_includes_students_who_have_not_submitted(): void
    {
        $this->actingAs($this->lecturer)
            ->get(route('dosen.assignments.submissions.index', $this->assignment))
            ->assertOk()
            ->assertSee('Mahasiswa Belum Mengumpulkan')
            ->assertSee('Belum mengumpulkan');
    }

    public function test_lecturer_from_another_course_receives_forbidden_response(): void
    {
        $otherLecturer = User::factory()->dosen()->create();

        $this->actingAs($otherLecturer)
            ->get(route('dosen.assignments.submissions.index', $this->assignment))
            ->assertForbidden();
    }

    public function test_student_receives_forbidden_response(): void
    {
        $this->actingAs($this->submittedStudent)
            ->get(route('dosen.assignments.submissions.index', $this->assignment))
            ->assertForbidden();
    }

    public function test_monitoring_summary_counts_submitted_and_unsubmitted_students(): void
    {
        $this->actingAs($this->lecturer)
            ->get(route('dosen.assignments.submissions.index', $this->assignment))
            ->assertOk()
            ->assertSee('Mahasiswa terdaftar: 2')
            ->assertSee('Sudah mengumpulkan: 1')
            ->assertSee('Belum mengumpulkan: 1');
    }

    public function test_grade_and_ungraded_status_are_displayed(): void
    {
        Grade::factory()->create([
            'submission_id' => $this->submission->id,
            'graded_by' => $this->lecturer->id,
            'score' => 87.5,
        ]);

        Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $this->unsubmittedStudent->id,
        ]);
        $thirdStudent = User::factory()->mahasiswa()->create([
            'name' => 'Mahasiswa Belum Dinilai',
        ]);
        $this->course->students()->attach($thirdStudent->id, ['enrolled_at' => now()]);
        Submission::factory()->create([
            'assignment_id' => $this->assignment->id,
            'user_id' => $thirdStudent->id,
        ]);

        $this->actingAs($this->lecturer)
            ->get(route('dosen.assignments.submissions.index', $this->assignment))
            ->assertOk()
            ->assertSee('87.50')
            ->assertSee('Belum dinilai');
    }

    public function test_empty_course_roster_has_an_explanatory_message(): void
    {
        $this->course->students()->detach();

        $this->actingAs($this->lecturer)
            ->get(route('dosen.assignments.submissions.index', $this->assignment))
            ->assertOk()
            ->assertSee('Mahasiswa terdaftar: 0')
            ->assertSee('Belum ada mahasiswa yang terdaftar pada mata kuliah ini.');
    }
}
