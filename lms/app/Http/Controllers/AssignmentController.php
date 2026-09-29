<?php

namespace App\Http\Controllers;

use App\Models\Assignment;

class AssignmentController extends Controller
{
    public function show(Assignment $assignment)
    {
        $user = auth()->user();

        abort_unless(
            $user->role === 'admin'
                || $assignment->course->lecturer_id === $user->id,
            403
        );

        return view('assignments.show', compact('assignment'));
    }
}
