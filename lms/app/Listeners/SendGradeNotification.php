<?php

namespace App\Listeners;

use App\Events\GradeGiven;
use App\Notifications\GradeReleasedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendGradeNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $backoff = 10;

    public function handle(GradeGiven $event): void
    {
        $submission = $event->grade->submission;

        if (! $submission) {
            $submission = $event->grade->submission()->first();
        }

        $student = $submission?->student ?? $submission?->user;

        if ($student) {
            $student->notify(new GradeReleasedNotification($event->grade));
        }
    }

    public function failed($event, ?Throwable $exception = null): void
    {
        $e = $exception ?? ($event instanceof Throwable ? $event : null);
        $id = $event instanceof GradeGiven ? $event->grade->id : 'unknown';
        Log::error("SendGradeNotification gagal untuk nilai [ID: {$id}]: " . ($e?->getMessage() ?? 'Unknown error'));
    }
}
