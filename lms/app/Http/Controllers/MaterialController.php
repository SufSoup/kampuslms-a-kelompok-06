<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Models\Course;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function index(Course $course): View
    {
        Gate::authorize('viewAny', [Material::class, $course]);

        $materials = $course->materials()->latest()->get();

        return view('materials.index', compact('course', 'materials'));
    }

    public function create(Course $course): View
    {
        Gate::authorize('create', [Material::class, $course]);

        return view('materials.create', compact('course'));
    }

    public function store(StoreMaterialRequest $request, Course $course): RedirectResponse
    {
        Gate::authorize('create', [Material::class, $course]);

        $validated = $request->validated();

        $material = new Material();
        $material->fill($validated);
        $material->course_id = $course->id;
        $material->uploaded_by = auth()->id();
        $material->save();

        return redirect()->route(auth()->user()->role === 'admin' ? 'admin.courses.show' : 'dosen.courses.show', $course)
            ->with('success', 'Materi berhasil ditambahkan.');
    }

    public function show(Material $material): View
    {
        Gate::authorize('view', $material);

        $material->load('course', 'uploader');

        return view('materials.show', compact('material'));
    }

    public function edit(Course $course, Material $material): View
    {
        abort_unless($material->course_id === $course->id, 404);
        Gate::authorize('update', $material);

        return view('materials.edit', compact('course', 'material'));
    }

    public function update(UpdateMaterialRequest $request, Course $course, Material $material): RedirectResponse
    {
        abort_unless($material->course_id === $course->id, 404);
        Gate::authorize('update', $material);

        $material->update($request->validated());

        return redirect()->route(auth()->user()->role === 'admin' ? 'admin.courses.show' : 'dosen.courses.show', $course)
            ->with('success', 'Materi berhasil diperbarui.');
    }

    public function destroy(Course $course, Material $material): RedirectResponse
    {
        abort_unless($material->course_id === $course->id, 404);
        Gate::authorize('delete', $material);

        $material->delete();

        return redirect()->route(auth()->user()->role === 'admin' ? 'admin.courses.show' : 'dosen.courses.show', $course)
            ->with('success', 'Materi berhasil dihapus.');
    }
}