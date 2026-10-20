<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaterialResource;
use App\Models\Course;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class MaterialController extends Controller
{
    /**
     * Display materials for a course the authenticated user can access.
     */
    public function index(Request $request, Course $course): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', [Material::class, $course]);

        $materials = $course->materials()
            ->latest()
            ->paginate(15);

        return MaterialResource::collection($materials);
    }
}
