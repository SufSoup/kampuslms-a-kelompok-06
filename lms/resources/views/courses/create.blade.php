<x-layout title="Tambah Mata Kuliah">
    <h1>Tambah Mata Kuliah</h1>

    @include('courses._form', [
        'formAction' => route('courses.store'),
        'method' => 'POST',
    ])

    <a href="{{ route('courses.index') }}">Kembali</a>
</x-layout>
