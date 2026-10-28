<?php

namespace App\Http\Controllers;

use App\Events\GradeGiven;
use App\Http\Requests\StoreSubmissionRequest;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    public function index(Assignment $assignment)
    {
        Gate::authorize('viewAny', [Submission::class, $assignment]);

        $assignment->load('course');

        $students = $assignment->course->students()
            ->where('users.role', 'mahasiswa')
            ->with([
                'submissions' => fn (HasMany $query) => $query
                    ->where('assignment_id', $assignment->id)
                    ->with('grade'),
            ])
            ->orderBy('users.name')
            ->get();

        $registeredCount = $students->count();
        $submittedCount = $students->filter(
            fn ($student) => $student->submissions->isNotEmpty()
        )->count();
        $notSubmittedCount = $registeredCount - $submittedCount;

        return view('dosen.submissions.index', compact(
            'assignment',
            'students',
            'registeredCount',
            'submittedCount',
            'notSubmittedCount'
        ));
    }

    public function store(StoreSubmissionRequest $request, Assignment $assignment): RedirectResponse
    {
        Gate::authorize('create', [Submission::class, $assignment]);

        if (! $assignment->allow_late && $assignment->due_at !== null && now()->gt($assignment->due_at)) {
            return back()->withErrors(['file' => 'Batas waktu pengumpulan tugas telah berakhir dan pengumpulan terlambat tidak diizinkan.']);
        }

        $file = $request->file('file');
        $path = $file->store("submissions/{$assignment->id}", 'local');

        if ($path === false) {
            throw new RuntimeException('Berkas tugas gagal disimpan.');
        }

        $existing = Submission::where('assignment_id', $assignment->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing && $existing->file_path && Storage::disk('local')->exists($existing->file_path)) {
            Storage::disk('local')->delete($existing->file_path);
        }

        Submission::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'user_id' => $request->user()->id,
            ],
            [
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'note' => $request->input('note'),
                'submitted_at' => now(),
                'is_late' => $assignment->due_at !== null && now()->gt($assignment->due_at),
            ]
        );

        return back()->with('success', 'Tugas berhasil dikumpulkan.');
    }

    public function show(Submission $submission)
    {
        Gate::authorize('view', $submission);

        $submission->load([
            'assignment.course',
            'student',
            'grade.grader',
        ]);

        return view('submissions.show', compact('submission'));
    }

    public function editGrade(Submission $submission): View
    {
        $submission->load(['assignment.course', 'student', 'grade']);

        if ($submission->grade) {
            Gate::authorize('update', $submission->grade);
        } else {
            Gate::authorize('create', [Grade::class, $submission]);
        }

        return view('dosen.submissions.grade', compact('submission'));
    }

    public function storeGrade(Request $request, Submission $submission): RedirectResponse
    {
        $submission->load('assignment.course');

        $grade = $submission->grade()->first();
        if ($grade) {
            Gate::authorize('update', $grade);
        } else {
            Gate::authorize('create', [Grade::class, $submission]);
        }

        $validated = $request->validate([
            'score' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:'.$submission->assignment->max_score,
            ],
            'feedback' => ['nullable', 'string'],
        ]);

        $grade ??= new Grade;
        $grade->submission()->associate($submission);
        $grade->graded_by = $request->user()->id;
        $grade->score = $validated['score'];
        $grade->feedback = $validated['feedback'] ?? null;
        $grade->graded_at = now();
        $grade->save();

        GradeGiven::dispatch($grade);

        return redirect()
            ->route('dosen.assignments.submissions.index', $submission->assignment)
            ->with('success', 'Nilai berhasil disimpan.');
    }

    public function courseGrades(Course $course): View
    {
        Gate::authorize('view', $course);

        $course->load('lecturer');
        $submissions = Submission::query()
            ->whereHas('assignment', fn ($query) => $query->where('course_id', $course->id))
            ->with(['student', 'assignment', 'grade.grader'])
            ->latest('submitted_at')
            ->paginate(25);

        return view('dosen.grades.index', compact('course', 'submissions'));
    }

    public function studentGrades(Request $request): View
    {
        $submissions = $request->user()
            ->submissions()
            ->with(['assignment.course', 'grade.grader'])
            ->latest('submitted_at')
            ->paginate(25);

        return view('mahasiswa.grades.index', compact('submissions'));
    }

    public function download(Submission $submission): StreamedResponse
    {
        Gate::authorize('view', $submission);

        abort_unless(
            $submission->file_path && Storage::disk('local')->exists($submission->file_path),
            404
        );

        return Storage::disk('local')->download(
            $submission->file_path,
            $submission->original_name ?? basename($submission->file_path)
        );
    }
}
