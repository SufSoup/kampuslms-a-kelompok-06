<x-layout title="{{ $material->title }}">
    <h1>{{ $material->title }}</h1>

    <p>{{ $material->description }}</p>
    <p>Mata Kuliah: {{ $material->course->name }}</p>
    <p>Jenis: {{ $material->type === 'file' ? 'Berkas' : 'Tautan' }}</p>

    @if ($material->type === 'link' && $material->external_url)
        <p>URL referensi: {{ $material->external_url }}</p>
    @endif
</x-layout>