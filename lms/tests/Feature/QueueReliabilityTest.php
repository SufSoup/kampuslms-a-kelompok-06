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
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class AlwaysFailingQueueJob implements ShouldQueue
{
    public int $tries = 1;

    public function handle(): void
    {
        throw new RuntimeException('Simulated queue failure.');
    }
}

class QueueReliabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_listeners_are_dispatched_as_queued_jobs(): void
    {
        Queue::fake();

        $assignment = Assignment::factory()->create();
        $grade = Grade::factory()->create();

        AssignmentPublished::dispatch($assignment);
        GradeGiven::dispatch($grade);

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job): bool {
            return in_array($job->class, [
                SendAssignmentNotification::class,
                SendGradeNotification::class,
            ], true);
        });

        $queuedListeners = Queue::pushed(CallQueuedListener::class);

        $this->assertGreaterThanOrEqual(2, $queuedListeners->count());
        $this->assertTrue($queuedListeners->contains(
            fn (CallQueuedListener $job): bool => $job->class === SendAssignmentNotification::class
        ));
        $this->assertTrue($queuedListeners->contains(
            fn (CallQueuedListener $job): bool => $job->class === SendGradeNotification::class
        ));
    }

    public function test_assignment_notification_is_sent_only_to_enrolled_students(): void
    {
        Notification::fake();

        $lecturer = User::factory()->dosen()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $enrolledStudent = User::factory()->mahasiswa()->create();
        $otherStudent = User::factory()->mahasiswa()->create();
        $course->students()->attach($enrolledStudent->id, ['enrolled_at' => now()]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'status' => 'published',
        ]);

        (new SendAssignmentNotification())->handle(new AssignmentPublished($assignment));

        Notification::assertSentTo($enrolledStudent, NewAssignmentNotification::class);
        Notification::assertNotSentTo($otherStudent, NewAssignmentNotification::class);
    }

    public function test_grade_notification_is_sent_to_submission_owner(): void
    {
        Notification::fake();

        $lecturer = User::factory()->dosen()->create();
        $student = User::factory()->mahasiswa()->create();
        $course = Course::factory()->create(['lecturer_id' => $lecturer->id]);
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'status' => 'published',
        ]);
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);
        $grade = Grade::factory()->create([
            'submission_id' => $submission->id,
            'graded_by' => $lecturer->id,
        ]);

        (new SendGradeNotification())->handle(new GradeGiven($grade));

        Notification::assertSentTo($student, GradeReleasedNotification::class);
    }

    public function test_failed_queue_job_is_recorded_in_failed_jobs(): void
    {
        config(['queue.default' => 'database']);

        dispatch(new AlwaysFailingQueueJob());

        Artisan::call('queue:work', [
            '--once' => true,
            '--tries' => 1,
        ]);

        $this->assertDatabaseHas('failed_jobs', [
            'queue' => 'default',
        ]);
    }

    public function test_listener_retry_configuration_is_explicit(): void
    {
        $assignmentListener = new SendAssignmentNotification();
        $gradeListener = new SendGradeNotification();

        $this->assertInstanceOf(ShouldQueue::class, $assignmentListener);
        $this->assertSame(3, $assignmentListener->tries);
        $this->assertSame(10, $assignmentListener->backoff);
        $this->assertInstanceOf(ShouldQueue::class, $gradeListener);
        $this->assertSame(3, $gradeListener->tries);
        $this->assertSame(10, $gradeListener->backoff);
    }
}
