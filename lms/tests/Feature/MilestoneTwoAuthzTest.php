<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

class MilestoneTwoAuthzTest extends TestCase
{
    use RefreshDatabase;

    public function test_lecturer_can_manage_assignments_for_their_course(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $this->actingAs($lecturer);

        $this->get(route('dosen.assignments.index'))->assertOk();
        $this->get(route('dosen.courses.assignments.create', $course))->assertOk();

        $this->post(route('dosen.courses.assignments.store', $course), [
            'title' => 'Tugas Web',
            'instructions' => 'Buat halaman sederhana.',
            'due_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'max_score' => 90,
            'allow_late' => '1',
            'status' => 'published',
        ])->assertRedirect();

        $assignment = Assignment::query()->firstOrFail();
        $this->assertSame($course->id, $assignment->course_id);
        $this->assertSame($lecturer->id, $assignment->created_by);

        $this->get(route('dosen.assignments.show', $assignment))->assertOk();
        $this->get(route('dosen.assignments.edit', $assignment))->assertOk();
        $this->put(route('dosen.assignments.update', $assignment), [
            'title' => 'Tugas Web Revisi',
            'instructions' => 'Instruksi baru.',
            'due_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'max_score' => 100,
            'allow_late' => '0',
            'status' => 'draft',
        ])->assertRedirect();

        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'title' => 'Tugas Web Revisi',
            'status' => 'draft',
        ]);

        $this->delete(route('dosen.assignments.destroy', $assignment))
            ->assertRedirect(route('dosen.assignments.index'));

        $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
    }

    public function test_lecturer_cannot_view_or_manage_assignments_for_another_course(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $owner = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $owner->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);

        $this->actingAs($lecturer)
            ->get(route('dosen.assignments.index'))
            ->assertOk()
            ->assertDontSee($assignment->title);

        $this->get(route('dosen.courses.assignments.create', $course))->assertForbidden();
        $this->post(route('dosen.courses.assignments.store', $course), [
            'title' => 'Tidak diizinkan',
            'instructions' => 'Tidak diizinkan.',
            'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'max_score' => 100,
            'allow_late' => '1',
            'status' => 'published',
        ])->assertForbidden();
        $this->get(route('dosen.assignments.show', $assignment))->assertForbidden();
        $this->get(route('dosen.assignments.edit', $assignment))->assertForbidden();
        $this->put(route('dosen.assignments.update', $assignment), [
            'title' => 'Diubah',
            'instructions' => 'Diubah.',
            'due_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'max_score' => 100,
            'allow_late' => '1',
            'status' => 'published',
        ])->assertForbidden();
        $this->delete(route('dosen.assignments.destroy', $assignment))->assertForbidden();
    }

    public function test_enrolled_student_can_view_only_published_assignments(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $published = Assignment::factory()->create([
            'course_id' => $course->id,
            'title' => 'Tugas Terbit Mahasiswa',
            'status' => 'published',
        ]);
        $draft = Assignment::factory()->draft()->create([
            'course_id' => $course->id,
            'title' => 'Tugas Draft Belum Terbit',
        ]);

        $this->actingAs($student)
            ->get(route('mahasiswa.assignments.index'))
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee($draft->title);

        $this->get(route('mahasiswa.assignments.show', $published))->assertOk();
        $this->get(route('mahasiswa.assignments.show', $draft))->assertForbidden();
    }

    public function test_unenrolled_student_cannot_view_an_assignment(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $assignment = Assignment::factory()->create();

        $this->actingAs($student)
            ->get(route('mahasiswa.assignments.show', $assignment))
            ->assertForbidden();
    }

    public function test_student_cannot_use_lecturer_assignment_crud_routes(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);
        $this->actingAs($student);

        $this->get(route('dosen.assignments.index'))->assertForbidden();
        $this->get(route('dosen.courses.assignments.create', $course))->assertForbidden();
        $this->post(route('dosen.courses.assignments.store', $course), [])->assertForbidden();
        $this->get(route('dosen.assignments.edit', $assignment))->assertForbidden();
        $this->put(route('dosen.assignments.update', $assignment), [])->assertForbidden();
        $this->delete(route('dosen.assignments.destroy', $assignment))->assertForbidden();
    }

    public function test_student_can_see_only_their_own_submission(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $otherStudent = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();
        $course->students()->attach([$student->id, $otherStudent->id], ['enrolled_at' => now()]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);
        $ownSubmission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);
        $otherSubmission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $otherStudent->id,
        ]);

        $this->actingAs($student)
            ->get(route('mahasiswa.submissions.show', $ownSubmission))
            ->assertOk();
        $this->get(route('mahasiswa.submissions.show', $otherSubmission))->assertForbidden();
    }

    public function test_lecturer_can_view_submissions_only_for_their_course(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $otherLecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $otherCourse = Course::factory()->create(['lecturer_id' => $otherLecturer->id]);
        $ownSubmission = Submission::factory()->create([
            'assignment_id' => Assignment::factory()->create(['course_id' => $course->id])->id,
        ]);
        $otherSubmission = Submission::factory()->create([
            'assignment_id' => Assignment::factory()->create(['course_id' => $otherCourse->id])->id,
        ]);

        $this->actingAs($lecturer)
            ->get(route('dosen.submissions.show', $ownSubmission))
            ->assertOk();
        $this->get(route('dosen.submissions.show', $otherSubmission))->assertForbidden();
    }

    public function test_grade_policy_limits_visibility_and_changes_to_owner_and_course(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $otherLecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $otherStudent = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $otherCourse = Course::factory()->create(['lecturer_id' => $otherLecturer->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);
        $otherAssignment = Assignment::factory()->create(['course_id' => $otherCourse->id]);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);
        $otherSubmission = Submission::factory()->create([
            'assignment_id' => $otherAssignment->id,
            'user_id' => $student->id,
        ]);
        $grade = Grade::factory()->create([
            'submission_id' => $submission->id,
            'graded_by' => $lecturer->id,
        ]);
        $otherStudentGrade = Grade::factory()->create([
            'submission_id' => Submission::factory()->create([
                'assignment_id' => $otherAssignment->id,
                'user_id' => $otherStudent->id,
            ])->id,
        ]);

        $this->assertTrue(Gate::forUser($student)->allows('view', $grade));
        $this->assertFalse(Gate::forUser($otherStudent)->allows('view', $grade));
        $this->assertFalse(Gate::forUser($student)->allows('create', [Grade::class, $submission]));
        $this->assertFalse(Gate::forUser($student)->allows('update', $grade));
        $this->assertTrue(Gate::forUser($lecturer)->allows('create', [Grade::class, $submission]));
        $this->assertTrue(Gate::forUser($lecturer)->allows('update', $grade));
        $this->assertFalse(Gate::forUser($lecturer)->allows('create', [Grade::class, $otherSubmission]));
        $this->assertFalse(Gate::forUser($lecturer)->allows('update', $otherStudentGrade));
    }

    public function test_submission_policy_requires_enrollment_and_published_assignment_for_creation(): void
    {
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create();
        $publishedAssignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'status' => 'published',
        ]);
        $draftAssignment = Assignment::factory()->draft()->create(['course_id' => $course->id]);

        $this->assertFalse(Gate::forUser($student)->allows('create', [Submission::class, $publishedAssignment]));
        $course->students()->attach($student->id, ['enrolled_at' => now()]);
        $this->assertTrue(Gate::forUser($student)->allows('create', [Submission::class, $publishedAssignment]));
        $this->assertFalse(Gate::forUser($student)->allows('create', [Submission::class, $draftAssignment]));
    }

    public function test_assignment_course_and_creator_cannot_be_overridden_by_request_data(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $otherCourse = Course::factory()->create();

        $this->actingAs($lecturer)
            ->post(route('dosen.courses.assignments.store', $course), [
                'course_id' => $otherCourse->id,
                'created_by' => $otherCourse->lecturer_id,
                'title' => 'Tugas tervalidasi',
                'instructions' => 'Instruksi.',
                'due_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'max_score' => 100,
                'allow_late' => '1',
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('assignments', [
            'title' => 'Tugas tervalidasi',
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);
        $this->assertDatabaseMissing('assignments', [
            'title' => 'Tugas tervalidasi',
            'course_id' => $otherCourse->id,
        ]);
    }

    public function test_unauthenticated_users_are_redirected_from_web_assignment_routes(): void
    {
        $this->get(route('dosen.assignments.index'))
            ->assertRedirect(route('login'));
        $this->get(route('mahasiswa.assignments.index'))
            ->assertRedirect(route('login'));
    }

    public function test_login_regenerates_the_session_id(): void
    {
        $user = User::factory()->dosen()->create([
            'email' => 'session-test@kampuslms.test',
            'password' => 'password',
        ]);
        $this->startSession();
        $this->withSession(['_token' => Str::random(40)]);
        $originalSessionId = session()->getId();

        $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertNotSame($originalSessionId, session()->getId());
    }
}
