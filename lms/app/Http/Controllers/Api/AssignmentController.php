<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentResource;
use App\Models\Course;
use App\Http\Requests\Api\StoreAssignmentRequest;
use App\Http\Requests\Api\UpdateAssignmentRequest;
use App\Models\Assignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AssignmentController extends Controller
{
    public function index(Request $request, Course $course): AnonymousResourceCollection
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            // Admin boleh melihat assignment dari semua course
        } elseif ($user->role === 'dosen') {
            abort_unless($course->lecturer_id === $user->id, 403);
        } elseif ($user->role === 'mahasiswa') {
            abort_unless(
                $course->students()->where('users.id', $user->id)->exists(),
                403
            );
        }

        $assignments = $course->assignments()
            ->with('creator')
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($user->role === 'mahasiswa', function ($query) {
                $query->where('status', 'published');
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return AssignmentResource::collection($assignments);
    }

    public function store(StoreAssignmentRequest $request): JsonResponse
    {
        $course = Course::findOrFail($request->course_id);

        abort_unless(
            $course->lecturer_id === $request->user()->id,
            403
        );

        $assignment = new Assignment([
            'title' => $request->title,
            'instructions' => $request->instructions,
            'due_at' => $request->due_at,
            'max_score' => $request->max_score ?? 100,
            'allow_late' => $request->allow_late ?? true,
        ]);

        $assignment->course_id = $course->id;
        $assignment->created_by = $request->user()->id;
        $assignment->status = $request->status;
        $assignment->save();

        $assignment->load('creator');

        return (new AssignmentResource($assignment))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateAssignmentRequest $request,
        Assignment $assignment
    ): JsonResponse {
        abort_unless(
            (int) $assignment->course->lecturer_id === (int) $request->user()->id,
            403
        );

        $assignment->fill($request->validated());
        $assignment->save();

        $assignment->load('creator');

        return (new AssignmentResource($assignment))
            ->response()
            ->setStatusCode(200);
    }
    public function destroy(
        Request $request,
        Assignment $assignment
    ): JsonResponse {
        abort_unless(
            (int) $assignment->course->lecturer_id === (int) $request->user()->id,
            403
        );

        $assignment->delete();

        return response()->json(null, 204);
    }
}