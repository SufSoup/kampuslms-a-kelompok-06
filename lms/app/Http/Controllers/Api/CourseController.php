<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CourseController extends Controller
{
    /**
     * Display courses visible to the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $courses = $this->visibleCourses($request->user())
            ->with('lecturer')
            ->withCount(['materials', 'assignments'])
            ->latest()
            ->paginate(15);

        return CourseResource::collection($courses);
    }

    /**
     * Display a course the authenticated user is allowed to access.
     */
    public function show(Request $request, Course $course): CourseResource
    {
        Gate::authorize('view', $course);

        $course->load('lecturer')->loadCount(['materials', 'assignments']);

        return new CourseResource($course);
    }

    /**
     * Build the course scope for a user's role.
     *
     * @return Builder<Course>
     */
    private function visibleCourses(User $user): Builder
    {
        $query = Course::query();

        return match ($user->role) {
            'admin' => $query,
            'dosen' => $query->where('lecturer_id', $user->id),
            'mahasiswa' => $query->whereHas(
                'students',
                fn (Builder $students) => $students->whereKey($user->id)
            ),
            default => abort(403, 'Anda tidak memiliki akses ke mata kuliah.'),
        };
    }
}
