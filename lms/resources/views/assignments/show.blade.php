<x-layout title="{{ $assignment->title }}">
    <h1>{{ $assignment->title }}</h1>
    <p>Mata kuliah: {{ $assignment->course->name }} ({{ $assignment->course->code }})</p>
    <p>Dibuat oleh: {{ $assignment->creator->name }}</p>
    <p>Status: {{ ucfirst($assignment->status) }}</p>
    <p>Batas pengumpulan: {{ $assignment->due_at->format('d M Y H:i') }}</p>
    <p>Nilai maksimum: {{ $assignment->max_score }}</p>
    <p>Pengumpulan terlambat: {{ $assignment->allow_late ? 'Diizinkan' : 'Tidak diizinkan' }}</p>

    <h2>Instruksi</h2>
    <p>{{ $assignment->instructions }}</p>

    @if (auth()->user()->role === 'dosen')
        <a href="{{ route('dosen.assignments.edit', $assignment) }}">Edit Tugas</a>
        <form method="POST" action="{{ route('dosen.assignments.destroy', $assignment) }}" onsubmit="return confirm('Hapus tugas ini?');">
            @csrf
            @method('DELETE')
            <button type="submit">Hapus Tugas</button>
        </form>
        <a href="{{ route('dosen.assignments.index') }}">Kembali ke daftar tugas</a>
    @else
        <a href="{{ route('mahasiswa.assignments.index') }}">Kembali ke daftar tugas</a>
    @endif
</x-layout>
