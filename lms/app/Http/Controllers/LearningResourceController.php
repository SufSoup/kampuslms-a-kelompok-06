<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLearningResourceRequest;
use App\Http\Requests\UpdateLearningResourceRequest;
use App\Models\Course;
use App\Models\LearningResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LearningResourceController extends Controller
{
    /**
     * Form tambah sumber belajar untuk dosen pengampu / admin.
     */
    public function create(Course $course): View
    {
        Gate::authorize('create', [LearningResource::class, $course]);

        return view('learning_resources.create', compact('course'));
    }

    /**
     * Simpan sumber belajar baru.
     */
    public function store(StoreLearningResourceRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('create', [LearningResource::class, $course]);

        $validated = $request->validated();

        $resource = new LearningResource();
        $resource->fill($validated);
        $resource->course_id = $course->id;
        $resource->added_by = auth()->id();
        $resource->save();

        $redirectRoute = auth()->user()->role === 'admin' ? 'admin.courses.show' : 'dosen.courses.show';

        return redirect()->route($redirectRoute, $course)
            ->with('success', 'Sumber belajar tambahan berhasil ditambahkan.');
    }

    /**
     * Form ubah sumber belajar.
     */
    public function edit(Course $course, LearningResource $resource): View
    {
        abort_unless($resource->course_id === $course->id, 404);
        Gate::authorize('update', $resource);

        return view('learning_resources.edit', compact('course', 'resource'));
    }

    /**
     * Simpan perubahan sumber belajar.
     */
    public function update(UpdateLearningResourceRequest $request, Course $course, LearningResource $resource): RedirectResponse
    {
        abort_unless($resource->course_id === $course->id, 404);
        Gate::authorize('update', $resource);

        $resource->update($request->validated());

        $redirectRoute = auth()->user()->role === 'admin' ? 'admin.courses.show' : 'dosen.courses.show';

        return redirect()->route($redirectRoute, $course)
            ->with('success', 'Sumber belajar tambahan berhasil diperbarui.');
    }

    /**
     * Hapus sumber belajar.
     */
    public function destroy(Course $course, LearningResource $resource): RedirectResponse
    {
        abort_unless($resource->course_id === $course->id, 404);
        Gate::authorize('delete', $resource);

        $resource->delete();

        $redirectRoute = auth()->user()->role === 'admin' ? 'admin.courses.show' : 'dosen.courses.show';

        return redirect()->route($redirectRoute, $course)
            ->with('success', 'Sumber belajar tambahan berhasil dihapus.');
    }

    /**
     * Anti-IDOR Fitur 17:
     * Toggle status baca sumber belajar oleh mahasiswa (mengikat ke auth()->id()).
     */
    public function toggleRead(Request $request, LearningResource $resource): RedirectResponse
    {
        Gate::authorize('toggleRead', $resource);

        $user = auth()->user();
        $isRead = $resource->readers()->where('user_id', $user->id)->exists();

        if ($isRead) {
            $resource->readers()->detach($user->id);
            $msg = 'Status baca diperbarui: Belum dibaca.';
        } else {
            $resource->readers()->attach($user->id, ['read_at' => now()]);
            $msg = 'Status baca diperbarui: Sudah dibaca.';
        }

        return back()->with('success', $msg);
    }
}
