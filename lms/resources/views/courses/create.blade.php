<x-layout title="Tambah Mata Kuliah">
    <h1>Tambah Mata Kuliah</h1>

    @include('courses._form', [
        'formAction' => route('admin.courses.store'),
        'method' => 'POST',
    ])

    <a href="{{ route('admin.courses.index') }}">Kembali</a>
</x-layout>
