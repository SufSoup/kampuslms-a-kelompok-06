<x-layout title="Edit Mata Kuliah — KampusLMS">
    <div class="breadcrumb">
        <a href="{{ route('admin.courses.index') }}"><x-icon name="arrow-left" class="icon-sm" /> Daftar Mata Kuliah</a>
        <x-icon name="chevron-right" class="icon-sm" />
        <span>Edit</span>
    </div>

    <div class="page-header">
        <h1 class="page-title">Edit Mata Kuliah</h1>
        <p class="page-desc">Perbarui informasi mata kuliah {{ $course->name }}.</p>
    </div>

    <div class="card" style="max-width: 640px;">
        @include('courses._form', [
            'formAction' => route('admin.courses.update', $course),
            'method' => 'PUT',
        ])
    </div>

    <p style="margin-top: 20px;">
        <a href="{{ route('admin.courses.show', $course) }}" class="link-muted"><x-icon name="arrow-left" class="icon-sm" /> Batal</a>
    </p>
</x-layout>
