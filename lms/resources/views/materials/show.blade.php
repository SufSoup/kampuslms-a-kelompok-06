<x-layout title="{{ $material->title }}">
    @php
        $routePrefix = match (auth()->user()->role) {
            'admin' => 'admin',
            'dosen' => 'dosen',
            'mahasiswa' => 'mahasiswa',
        };
    @endphp
    <p>
        <a href="{{ route($routePrefix . '.courses.materials.index', $material->course) }}">
            ← Kembali ke daftar materi {{ $material->course->name }}
        </a>
    </p>
    <h1>{{ $material->title }}</h1>

    <p>{{ $material->description }}</p>
    <p>Mata Kuliah: {{ $material->course->name }}</p>
    <p>Jenis: {{ $material->type === 'file' ? 'Berkas' : 'Tautan' }}</p>

    @if ($material->type === 'link' && $material->external_url)
        <p>URL referensi: <a href="{{ $material->external_url }}" target="_blank" rel="noopener noreferrer">{{ $material->external_url }}</a></p>
    @elseif ($material->type === 'file' && $material->file_path)
        <p>
            Berkas materi:
            <a href="{{ route($routePrefix . '.materials.download', $material) }}">
                Unduh {{ $material->original_name ?? 'berkas materi' }}
            </a>
        </p>
    @endif
</x-layout>