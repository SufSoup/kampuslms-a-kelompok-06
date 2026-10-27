<?php

namespace App\Http\Controllers\Api;

use App\Events\GradeGiven;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubmissionResource;
use App\Models\Assignment;
use App\Models\Submission;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\Api\StoreSubmissionRequest;
use App\Http\Requests\Api\GradeSubmissionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;


class SubmissionController extends Controller
{
    public function index(
        Request $request,
        Assignment $assignment
    ): AnonymousResourceCollection {
        Gate::authorize('viewAny', [Submission::class, $assignment]);

        $submissions = $assignment->submissions()
            ->with(['student', 'grade'])
            ->latest()
            ->paginate(15);

        return SubmissionResource::collection($submissions);
    }

    public function store(
        StoreSubmissionRequest $request,
        Assignment $assignment
    ): JsonResponse {
        Gate::authorize('create', [Submission::class, $assignment]);

        $user = $request->user();

        if ($assignment->submissions()
            ->where('user_id', $user->id)
            ->exists()
        ) {
            throw ValidationException::withMessages([
                'file' => ['Anda sudah mengumpulkan tugas ini.'],
            ]);
        }

        $file = $request->file('file');

        $path = $file->store('submissions');

        $submittedAt = now();

        $submission = new Submission();

        $submission->assignment_id = $assignment->id;
        $submission->user_id = $user->id;
        $submission->file_path = $path;
        $submission->original_name = $file->getClientOriginalName();
        $submission->file_size = $file->getSize();
        $submission->note = $request->input('note');
        $submission->submitted_at = $submittedAt;
        $submission->is_late = $assignment->due_at !== null
            && $submittedAt->greaterThan($assignment->due_at);

        $submission->save();

        $submission->load(['student', 'grade']);

        return (new SubmissionResource($submission))
            ->response()
            ->setStatusCode(201);
    }
    public function grade(
        GradeSubmissionRequest $request,
        Submission $submission
    ): JsonResponse {
        $grade = Grade::where('submission_id', $submission->id)->first();

        if ($grade) {
            Gate::authorize('update', $grade);

            $grade->score = $request->score;
            $grade->feedback = $request->feedback;
            $grade->graded_by = $request->user()->id;
            $grade->graded_at = now();
            $grade->save();

            $statusCode = 200;
        } else {
            Gate::authorize('create', [Grade::class, $submission]);

            $grade = new Grade();

            $grade->submission_id = $submission->id;
            $grade->graded_by = $request->user()->id;
            $grade->score = $request->score;
            $grade->feedback = $request->feedback;
            $grade->graded_at = now();
            $grade->save();

            $statusCode = 201;
        }

        $grade->load('grader');

        GradeGiven::dispatch($grade);

        return (new \App\Http\Resources\GradeResource($grade))
            ->response()
            ->setStatusCode($statusCode);
    }
}