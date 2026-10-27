<?php

namespace App\Notifications;

use App\Models\Grade;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

class GradeReleasedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Grade $grade
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $submission = $this->grade->submission;
        $assignment = $submission?->assignment;
        $assignmentId = $assignment?->id ?? $submission?->assignment_id;

        $url = Route::has('mahasiswa.assignments.show') && $assignment
            ? route('mahasiswa.assignments.show', $assignment)
            : (Route::has('assignments.show') && $assignment ? route('assignments.show', $assignment) : url("/mahasiswa/assignments/{$assignmentId}"));

        return [
            'submission_id'    => $this->grade->submission_id,
            'assignment_title' => $assignment?->title,
            'course_name'      => $assignment?->course?->name,
            'url'              => $url,
        ];
    }
}
