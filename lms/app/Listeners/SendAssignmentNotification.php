<?php

namespace App\Listeners;

use App\Events\AssignmentPublished;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendAssignmentNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 10;

    public function handle(AssignmentPublished $event): void
    {
        $assignment = $event->assignment;
        $course = $assignment->course;

        if (! $course) {
            return;
        }

        $students = $course->students()->where('role', 'mahasiswa')->get();

        if ($students->isNotEmpty()) {
            Notification::send($students, new NewAssignmentNotification($assignment));
        }
    }

    public function failed($event, ?Throwable $exception = null): void
    {
        $e = $exception ?? ($event instanceof Throwable ? $event : null);
        $id = $event instanceof AssignmentPublished ? $event->assignment->id : 'unknown';
        Log::error("SendAssignmentNotification gagal untuk tugas [ID: {$id}]: " . ($e?->getMessage() ?? 'Unknown error'));
    }
}
