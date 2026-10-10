<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Support\Facades\Gate;

class SubmissionController extends Controller
{
    public function show(Submission $submission)
    {
        Gate::authorize('view', $submission);

        $submission->load([
            'assignment.course',
            'student',
            'grade.grader',
        ]);

        return view('submissions.show', compact('submission'));
    }
}