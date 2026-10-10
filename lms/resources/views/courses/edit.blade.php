<x-layout title="Edit Mata Kuliah">
    <h1>Edit Mata Kuliah</h1>

    @include('courses._form', [
        'formAction' => route('admin.courses.update', $course),
        'method' => 'PUT',
    ])

    <a href="{{ route('admin.courses.show', $course) }}">Batal</a>
</x-layout>
