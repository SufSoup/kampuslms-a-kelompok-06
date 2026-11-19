<x-layout title="Tambah Mata Kuliah — KampusLMS">
    <div class="breadcrumb">
        <a href="{{ route('admin.courses.index') }}"><x-icon name="arrow-left" class="icon-sm" /> Daftar Mata Kuliah</a>
    </div>

    <div class="page-header">
        <h1 class="page-title">Tambah Mata Kuliah</h1>
        <p class="page-desc">Lengkapi informasi mata kuliah baru di bawah ini.</p>
    </div>

    <div class="card" style="max-width: 640px;">
        @include('courses._form', [
            'formAction' => route('admin.courses.store'),
            'method' => 'POST',
        ])
    </div>

    <p style="margin-top: 20px;">
        <a href="{{ route('admin.courses.index') }}" class="link-muted"><x-icon name="arrow-left" class="icon-sm" /> Kembali</a>
    </p>
</x-layout>
