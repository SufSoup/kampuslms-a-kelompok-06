<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Models\Course;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

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

        $material = new Material;
        $material->fill($this->materialAttributes($request->validated()));
        $material->course()->associate($course);
        $material->uploader()->associate($request->user());

        if ($request->hasFile('file')) {
            $this->attachUploadedFile($request, $material);
        } elseif (($validated['type'] ?? null) === 'link') {
            $material->file_path = null;
            $material->original_name = null;
            $material->file_size = null;
            $material->mime_type = null;
        }

        $material->save();

        return redirect()->route($this->routePrefix($request->user()->role).'.courses.materials.index', $course)
            ->with('success', 'Materi berhasil ditambahkan.');
    }

    public function show(Material $material): View
    {
        Gate::authorize('view', $material);

        $material->load('course', 'uploader');

        return view('materials.show', compact('material'));
    }

    public function download(Material $material)
    {
        Gate::authorize('view', $material);

        abort_unless(
            $material->type === 'file'
                && $material->file_path
                && Storage::disk('local')->exists($material->file_path),
            404
        );

        return Storage::disk('local')->download(
            $material->file_path,
            $material->original_name ?? basename($material->file_path)
        );
    }

    public function edit(Material $material): View
    {
        Gate::authorize('update', $material);

        $course = $material->course;

        return view('materials.edit', compact('course', 'material'));
    }

    public function update(UpdateMaterialRequest $request, Material $material): RedirectResponse
    {
        Gate::authorize('update', $material);

        $course = $material->course;
        $validated = $request->validated();
        $oldFilePath = $material->file_path;
        $material->fill($this->materialAttributes($validated, $material));

        if ($request->hasFile('file')) {
            $this->attachUploadedFile($request, $material);
        } elseif (($validated['type'] ?? $material->type) === 'link') {
            $material->file_path = null;
            $material->original_name = null;
            $material->file_size = null;
            $material->mime_type = null;
        }

        $material->save();

        if ($oldFilePath && $oldFilePath !== $material->file_path) {
            $this->deleteStoredFile($oldFilePath);
        }

        return redirect()->route($this->routePrefix(auth()->user()->role).'.courses.materials.index', $course)
            ->with('success', 'Materi berhasil diperbarui.');
    }

    public function destroy(Material $material): RedirectResponse
    {
        Gate::authorize('delete', $material);

        $course = $material->course;
        $filePath = $material->file_path;
        $material->delete();

        if ($filePath) {
            $this->deleteStoredFile($filePath);
        }

        return redirect()->route($this->routePrefix(auth()->user()->role).'.courses.materials.index', $course)
            ->with('success', 'Materi berhasil dihapus.');
    }

    private function materialAttributes(array $validated, ?Material $material = null): array
    {
        $type = $validated['type'] ?? $material?->type;

        return [
            'title' => $validated['title'] ?? $material?->title,
            'description' => array_key_exists('description', $validated)
                ? ($validated['description'] ?? '')
                : ($material?->description ?? ''),
            'type' => $type,
            'external_url' => $type === 'link'
                ? (array_key_exists('external_url', $validated)
                    ? $validated['external_url']
                    : $material?->external_url)
                : null,
        ];
    }

    private function attachUploadedFile(Request $request, Material $material): void
    {
        $file = $request->file('file');
        $path = $file->store('materials', 'local');

        if ($path === false) {
            throw new RuntimeException('Berkas materi gagal disimpan.');
        }

        $material->file_path = $path;
        $material->original_name = $file->getClientOriginalName();
        $material->file_size = $file->getSize();
        $material->mime_type = $file->getMimeType();
    }

    private function deleteStoredFile(string $path): void
    {
        if (Storage::disk('local')->exists($path) && ! Storage::disk('local')->delete($path)) {
            throw new RuntimeException('Berkas materi lama gagal dihapus dari penyimpanan.');
        }
    }

    private function routePrefix(string $role): string
    {
        return $role === 'admin' ? 'admin' : 'dosen';
    }
}
