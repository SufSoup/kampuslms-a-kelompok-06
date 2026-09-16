<x-layout title="{{ $course->name }}">
    <h1>{{ $course->name }}</h1>

    <p>Kode: {{ $course->code }}</p>
    <p>Deskripsi: {{ $course->description }}</p>
    <p>SKS: {{ $course->sks }}</p>
    <p>Dosen: {{ $course->lecturer?->name ?? 'Dosen tidak tersedia' }}</p>
    <p>Status: {{ $course->status }}</p>

    <a href="{{ route('courses.edit', $course) }}">Edit</a>
    <a href="{{ route('courses.index') }}">Kembali ke daftar</a>
</x-layout>