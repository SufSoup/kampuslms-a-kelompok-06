<x-layout title="Edit Mata Kuliah">
    <h1>Edit Mata Kuliah</h1>

    @include('courses._form', [
        'formAction' => route('courses.update', $course),
        'method' => 'PUT',
    ])

    <a href="{{ route('courses.show', $course) }}">Batal</a>
</x-layout>
