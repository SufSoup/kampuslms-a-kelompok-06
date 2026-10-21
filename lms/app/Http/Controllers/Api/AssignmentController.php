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
use Illuminate\Support\Facades\Gate;

class AssignmentController extends Controller
{
    public function index(Request $request, Course $course): AnonymousResourceCollection
    {
        Gate::authorize('view', $course);

        $user = $request->user();

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

        Gate::authorize('create', [Assignment::class, $course]);

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
        Gate::authorize('update', $assignment);

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
        Gate::authorize('delete', $assignment);

        $assignment->delete();

        return response()->json(null, 204);
    }
}