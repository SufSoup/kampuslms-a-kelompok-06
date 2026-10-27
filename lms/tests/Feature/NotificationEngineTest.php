<?php

namespace Tests\Feature;

use App\Events\AssignmentPublished;
use App\Events\GradeGiven;
use App\Listeners\SendAssignmentNotification;
use App\Listeners\SendGradeNotification;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\GradeReleasedNotification;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_listeners_implement_should_queue_with_retry_and_backoff(): void
    {
        $assignmentListener = new SendAssignmentNotification();
        $this->assertInstanceOf(ShouldQueue::class, $assignmentListener);
        $this->assertSame(3, $assignmentListener->tries);
        $this->assertSame(10, $assignmentListener->backoff);

        $gradeListener = new SendGradeNotification();
        $this->assertInstanceOf(ShouldQueue::class, $gradeListener);
        $this->assertSame(3, $gradeListener->tries);
        $this->assertSame(10, $gradeListener->backoff);
    }

    public function test_publishing_assignment_sends_notification_only_to_enrolled_students(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $enrolledStudent = User::factory()->mahasiswa()->create();
        $otherStudent = User::factory()->mahasiswa()->create();

        $course->students()->attach($enrolledStudent->id, ['enrolled_at' => now()]);

        $this->actingAs($lecturer)->post(route('dosen.courses.assignments.store', $course), [
            'title' => 'Tugas 1 Pemrograman Web',
            'instructions' => 'Kerjakan tugas dengan teliti.',
            'due_at' => now()->addDays(7)->toDateTimeString(),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'published',
        ])->assertRedirect();

        $assignment = Assignment::where('title', 'Tugas 1 Pemrograman Web')->firstOrFail();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $enrolledStudent->id,
            'notifiable_type' => User::class,
            'type' => NewAssignmentNotification::class,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $otherStudent->id,
            'notifiable_type' => User::class,
            'type' => NewAssignmentNotification::class,
        ]);

        $notification = $enrolledStudent->notifications()->first();
        $this->assertSame($assignment->id, $notification->data['assignment_id']);
        $this->assertSame('Tugas 1 Pemrograman Web', $notification->data['title']);
        $this->assertSame($course->name, $notification->data['course_name']);
    }

    public function test_draft_assignment_does_not_send_notifications(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $enrolledStudent = User::factory()->mahasiswa()->create();
        $course->students()->attach($enrolledStudent->id, ['enrolled_at' => now()]);

        $this->actingAs($lecturer)->post(route('dosen.courses.assignments.store', $course), [
            'title' => 'Draf Tugas Belum Rilis',
            'instructions' => 'Instruksi rahasia.',
            'due_at' => now()->addDays(7)->toDateTimeString(),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'draft',
        ])->assertRedirect();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_grading_submission_sends_notification_only_to_submission_owner(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $studentOwner = User::factory()->mahasiswa()->create();
        $otherStudent = User::factory()->mahasiswa()->create();

        $course->students()->attach([
            $studentOwner->id => ['enrolled_at' => now()],
            $otherStudent->id => ['enrolled_at' => now()],
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'status' => 'published',
        ]);

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $studentOwner->id,
            'file_path' => 'submissions/test.pdf',
            'original_name' => 'test.pdf',
            'file_size' => 1024,
            'submitted_at' => now(),
            'is_late' => false,
        ]);

        $this->actingAs($lecturer, 'sanctum')->putJson("/api/v1/submissions/{$submission->id}/grade", [
            'score' => 95.50,
            'feedback' => 'Hasil pengerjaan sangat rapi dan lengkap.',
        ])->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $studentOwner->id,
            'notifiable_type' => User::class,
            'type' => GradeReleasedNotification::class,
        ]);

        $this->assertDatabaseMissing('notifications', [
            'notifiable_id' => $otherStudent->id,
            'notifiable_type' => User::class,
            'type' => GradeReleasedNotification::class,
        ]);
    }

    public function test_notification_payload_strictly_omits_score_and_feedback(): void
    {
        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $student = User::factory()->mahasiswa()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas Sanitasi Notifikasi',
            'status' => 'published',
        ]);

        $submission = Submission::create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'file_path' => 'submissions/test.pdf',
            'original_name' => 'test.pdf',
            'file_size' => 1024,
            'submitted_at' => now(),
            'is_late' => false,
        ]);

        $this->actingAs($lecturer, 'sanctum')->putJson("/api/v1/submissions/{$submission->id}/grade", [
            'score' => 88.00,
            'feedback' => 'Feedback rahasia tidak boleh bocor.',
        ])->assertSuccessful();

        $notification = $student->notifications()->first();
        $this->assertNotNull($notification);

        $payload = $notification->data;

        $this->assertArrayNotHasKey('score', $payload, 'Payload notifikasi tidak boleh memuat score.');
        $this->assertArrayNotHasKey('feedback', $payload, 'Payload notifikasi tidak boleh memuat feedback.');

        $this->assertSame($submission->id, $payload['submission_id']);
        $this->assertSame('Tugas Sanitasi Notifikasi', $payload['assignment_title']);
        $this->assertSame($course->name, $payload['course_name']);
        $this->assertArrayHasKey('url', $payload);
    }

    public function test_events_can_be_faked_independently(): void
    {
        Event::fake([AssignmentPublished::class]);

        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);

        $this->actingAs($lecturer)->post(route('dosen.courses.assignments.store', $course), [
            'title' => 'Tugas Test Event Fake',
            'instructions' => 'Instruksi tugas.',
            'due_at' => now()->addDays(5)->toDateTimeString(),
            'max_score' => 100,
            'allow_late' => true,
            'status' => 'published',
        ])->assertRedirect();

        Event::assertDispatched(AssignmentPublished::class);
    }
}
