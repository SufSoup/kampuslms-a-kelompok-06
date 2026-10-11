<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Http\Requests\StoreAssignmentRequest;
use App\Http\Requests\UpdateAssignmentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Assignment::class);

        $user = $request->user();

        $assignments = Assignment::query()
            ->with(['course', 'creator'])
            ->when(
                $user->role === 'dosen',
                fn ($query) => $query->whereHas(
                    'course',
                    fn ($courseQuery) => $courseQuery
                        ->where('lecturer_id', $user->id)
                )
            )
            ->when(
                $user->role === 'mahasiswa',
                fn ($query) => $query->whereHas(
                    'course.students',
                    fn ($studentQuery) => $studentQuery
                        ->where('users.id', $user->id)
                )->where('status', 'published')
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $courses = $user->role === 'dosen'
            ? $user->taughtCourses()->orderBy('name')->get()
            : collect();

        return view('assignments.index', compact('assignments', 'courses'));
    }

    public function create(Course $course)
    {
        Gate::authorize('create', [Assignment::class, $course]);

        return view('assignments.create', compact('course'));
    }

    public function store(StoreAssignmentRequest $request, Course $course)
    {
        Gate::authorize('create', [Assignment::class, $course]);

        $validated = $request->validated();

        $assignment = $course->assignments()->make([
            'title' => $validated['title'],
            'instructions' => $validated['instructions'],
            'due_at' => $validated['due_at'],
            'max_score' => $validated['max_score'],
            'allow_late' => $validated['allow_late'],
        ]);

        $assignment->creator()->associate($request->user());
        $assignment->status = $validated['status'];
        $assignment->save();

        return redirect()
            ->route('dosen.assignments.show', $assignment)
            ->with('success', 'Tugas berhasil dibuat.');
    }

    public function show(Assignment $assignment)
    {
        Gate::authorize('view', $assignment);

        $assignment->load(['course', 'creator']);

        return view('assignments.show', compact('assignment'));
    }

    public function edit(Assignment $assignment)
    {
        Gate::authorize('update', $assignment);

        $assignment->load('course');

        return view('assignments.edit', compact('assignment'));
    }

    public function update(UpdateAssignmentRequest $request, Assignment $assignment)
    {
        Gate::authorize('update', $assignment);

        $validated = $request->validated();

        $assignment->fill([
            'title' => $validated['title'],
            'instructions' => $validated['instructions'],
            'due_at' => $validated['due_at'],
            'max_score' => $validated['max_score'],
            'allow_late' => $validated['allow_late'],
        ]);

        $assignment->status = $validated['status'];
        $assignment->save();

        return redirect()
            ->route('dosen.assignments.show', $assignment)
            ->with('success', 'Tugas berhasil diperbarui.');
    }

    public function destroy(Assignment $assignment)
    {
        Gate::authorize('delete', $assignment);

        $assignment->delete();

        return redirect()
            ->route('dosen.assignments.index')
            ->with('success', 'Tugas berhasil dihapus.');
    }
}