<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaterialResource;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MaterialController extends Controller
{
    /**
     * Display materials for a course the authenticated user can access.
     */
    public function index(Request $request, Course $course): AnonymousResourceCollection
    {
        abort_unless($this->canAccessCourse($request->user(), $course), 403);

        $materials = $course->materials()
            ->latest()
            ->paginate(15);

        return MaterialResource::collection($materials);
    }

    private function canAccessCourse(User $user, Course $course): bool
    {
        return match ($user->role) {
            'admin' => true,
            'dosen' => $course->lecturer_id === $user->id,
            'mahasiswa' => $course->students()->whereKey($user->id)->exists(),
            default => false,
        };
    }
}
