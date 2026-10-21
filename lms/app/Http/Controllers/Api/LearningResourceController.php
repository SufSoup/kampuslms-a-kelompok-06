<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLearningResourceRequest;
use App\Http\Resources\LearningResourceResource;
use App\Models\Course;
use App\Models\LearningResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class LearningResourceController extends Controller
{
    /**
     * Display a listing of curated resources for a course.
     */
    public function index(Request $request, Course $course): AnonymousResourceCollection
    {
        Gate::authorize('view', $course);

        $resources = $course->learningResources()
            ->with('curator')
            ->with(['readers' => fn ($q) => $q->where('users.id', $request->user()->id)])
            ->latest()
            ->paginate(15);

        return LearningResourceResource::collection($resources);
    }

    /**
     * Store a newly created learning resource for a course.
     */
    public function store(StoreLearningResourceRequest $request, Course $course): JsonResponse
    {
        Gate::authorize('create', [LearningResource::class, $course]);

        $user = $request->user();
        $validated = $request->validated();
        $resource = new LearningResource($validated);
        $resource->course_id = $course->id;
        $resource->added_by = $user->id;
        $resource->save();

        $resource->load('curator');

        return (new LearningResourceResource($resource))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display a single learning resource.
     */
    public function show(Request $request, LearningResource $resource): LearningResourceResource
    {
        Gate::authorize('view', $resource);

        $resource->load('curator');

        return new LearningResourceResource($resource);
    }

    /**
     * Toggle read status of a learning resource for the authenticated student.
     * Anti-IDOR: strictly bounds the read record to auth()->id().
     */
    public function toggleRead(Request $request, LearningResource $resource): JsonResponse
    {
        Gate::authorize('toggleRead', $resource);

        $user = $request->user();
        $existing = $resource->readers()->where('users.id', $user->id)->first();

        if ($existing) {
            $resource->readers()->detach($user->id);

            return response()->json([
                'is_read' => false,
                'message' => 'Status sumber belajar ditandai belum dibaca.',
            ]);
        }

        $now = now();
        $resource->readers()->attach($user->id, [
            'read_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'is_read' => true,
            'read_at' => $now->toIso8601String(),
            'message' => 'Status sumber belajar ditandai sudah dibaca.',
        ]);
    }
}
