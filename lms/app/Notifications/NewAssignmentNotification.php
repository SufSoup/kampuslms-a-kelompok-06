<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

class NewAssignmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Assignment $assignment
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $assignmentId = $this->assignment->id;
        $url = Route::has('mahasiswa.assignments.show')
            ? route('mahasiswa.assignments.show', $this->assignment)
            : (Route::has('assignments.show') ? route('assignments.show', $this->assignment) : url("/mahasiswa/assignments/{$assignmentId}"));

        return [
            'assignment_id' => $assignmentId,
            'course_name'   => $this->assignment->course?->name,
            'title'         => $this->assignment->title,
            'due_at'        => $this->assignment->due_at?->toIso8601String(),
            'url'           => $url,
        ];
    }
}
