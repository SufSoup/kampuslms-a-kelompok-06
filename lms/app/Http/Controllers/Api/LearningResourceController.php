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

class LearningResourceController extends Controller
{
    /**
     * Display a listing of curated resources for a course.
     */
    public function index(Request $request, Course $course): AnonymousResourceCollection
    {
        $this->authorizeCourseAccess($request->user(), $course);

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
        $user = $request->user();

        // Only the course lecturer or admin can curate learning resources
        $canCurate = $user->role === 'admin' || ($user->role === 'dosen' && $course->lecturer_id === $user->id);
        abort_unless($canCurate, 403, 'Anda tidak memiliki hak untuk menambah sumber belajar pada mata kuliah ini.');

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
        $this->authorizeCourseAccess($request->user(), $resource->course);

        $resource->load('curator');

        return new LearningResourceResource($resource);
    }

    /**
     * Toggle read status of a learning resource for the authenticated student.
     * Anti-IDOR: strictly bounds the read record to auth()->id().
     */
    public function toggleRead(Request $request, LearningResource $resource): JsonResponse
    {
        $user = $request->user();
        $course = $resource->course;

        // User must be enrolled in the course to mark resource as read
        $isEnrolled = $course->students()->where('users.id', $user->id)->exists();
        abort_unless($isEnrolled, 403, 'Anda belum terdaftar pada mata kuliah ini.');

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

    /**
     * Check if user is allowed to view resources in the given course.
     */
    protected function authorizeCourseAccess($user, Course $course): void
    {
        if ($user->role === 'admin') {
            return;
        }

        if ($user->role === 'dosen' && $course->lecturer_id === $user->id) {
            return;
        }

        if ($user->role === 'mahasiswa' && $course->students()->where('users.id', $user->id)->exists()) {
            return;
        }

        abort(403, 'Anda tidak memiliki akses ke sumber daya ini.');
    }
}
