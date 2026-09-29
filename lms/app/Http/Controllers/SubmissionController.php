<?php

namespace App\Http\Controllers;

use App\Models\Submission;

class SubmissionController extends Controller
{
    public function show(Submission $submission)
    {
        $user = auth()->user();

        abort_unless(
            $submission->user_id === $user->id
                || $user->role === 'admin'
                || $submission->assignment->course->lecturer_id === $user->id,
            403
        );

        return view('submissions.show', compact('submission'));
    }
}