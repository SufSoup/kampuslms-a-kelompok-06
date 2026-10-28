<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = match ($user->role) {
            'admin' => Course::query(),
            'dosen' => Course::query()->where('lecturer_id', $user->id),
            'mahasiswa' => Course::query()->whereHas(
                'students',
                fn (Builder $query) => $query->whereKey($user->id)
            ),
            default => abort(403, 'Peran pengguna tidak memiliki akses ke daftar mata kuliah.'),
        };

        $courses = $query
            ->with('lecturer')
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->q . '%')
                      ->orWhere('code', 'like', '%' . $request->q . '%');
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('courses.index', compact('courses'));
    }

    public function create()
    {
        Gate::authorize('create', Course::class);

        $lecturers = User::where('role', 'dosen')->orderBy('name')->get();

        return view('courses.create', compact('lecturers'));
    }

    public function store(StoreCourseRequest $request)
    {
        Gate::authorize('create', Course::class);

        $validated = $request->validated();

        $lecturerId = $validated['lecturer_id'];
        unset($validated['lecturer_id']);

        $course = new Course();
        $course->fill($validated);
        $course->lecturer_id = $lecturerId;
        $course->save();

        return redirect()->route('admin.courses.index')->with('success', 'Mata kuliah berhasil dibuat.');
    }

    public function show(Course $course)
    {
        $user = auth()->user();

        Gate::authorize('view', $course);

        $course->load(['lecturer', 'learningResources.readers', 'materials']);
        $availableStudents = collect();

        if ($user && $user->role === 'admin') {
            $course->load([
                'students' => fn ($query) => $query
                    ->where('role', 'mahasiswa')
                    ->orderBy('name'),
            ]);

            $availableStudents = User::query()
                ->where('role', 'mahasiswa')
                ->whereDoesntHave('courses', fn (Builder $query) => $query->whereKey($course->id))
                ->orderBy('name')
                ->get();
        }

        return view('courses.show', compact('course', 'availableStudents'));
    }

    public function edit(Course $course)
    {
        Gate::authorize('update', $course);

        $lecturers = User::where('role', 'dosen')->orderBy('name')->get();

        return view('courses.edit', compact('course', 'lecturers'));
    }

    public function update(UpdateCourseRequest $request, Course $course)
    {
        Gate::authorize('update', $course);

        $validated = $request->validated();

        $lecturerId = $validated['lecturer_id'];
        unset($validated['lecturer_id']);

        $course->fill($validated);
        $course->lecturer_id = $lecturerId;
        $course->save();

        return redirect()->route('admin.courses.show', $course)->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    public function destroy(Course $course)
    {
        Gate::authorize('delete', $course);

        $course->materials()->chunkById(100, static function ($materials): void {
            $materials->each(static function (Material $material): void {
                $material->delete();
            });
        });

        $course->assignments()->chunkById(100, static function ($assignments): void {
            $assignments->each(static function (Assignment $assignment): void {
                $assignment->submissions()->chunkById(100, static function ($submissions): void {
                    $submissions->each(static function (Submission $submission): void {
                        $submission->delete();
                    });
                });
            });
        });

        $course->delete();

        return redirect()->route('admin.courses.index')->with('success', 'Mata kuliah berhasil dihapus.');
    }
}
