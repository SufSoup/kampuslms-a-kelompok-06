<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $data = match ($user->role) {
            'admin' => [
                'stats' => [
                    'users' => User::query()->count(),
                    'courses' => Course::query()->count(),
                    'materials' => Material::query()->count(),
                    'assignments' => Assignment::query()->count(),
                ],
            ],
            'dosen' => [
                'taughtCourses' => $user->taughtCourses()
                    ->withCount(['students', 'materials', 'assignments'])
                    ->orderBy('name')
                    ->get(),
            ],
            'mahasiswa' => $this->studentDashboardData($user),
            default => abort(403, 'Peran pengguna tidak memiliki akses ke dashboard.'),
        };

        return view('dashboard', $data);
    }

    private function studentDashboardData(User $user): array
    {
        $activeAssignments = Assignment::query()
            ->with('course')
            ->where('status', 'published')
            ->where('due_at', '>=', now())
            ->whereHas('course.students', fn ($query) => $query->whereKey($user->id));

        return [
            'enrolledCourses' => $user->courses()
                ->with('lecturer')
                ->withCount(['materials', 'assignments'])
                ->orderBy('name')
                ->get(),
            'activeAssignments' => (clone $activeAssignments)
                ->orderBy('due_at')
                ->limit(5)
                ->get(),
            'activeAssignmentsCount' => $activeAssignments->count(),
        ];
    }
}
