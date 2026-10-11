<x-layout title="Detail Pengumpulan">
    <h1>Detail Pengumpulan</h1>
    <h2>{{ $submission->assignment->title }}</h2>
    <p>Mata kuliah: {{ $submission->assignment->course->name }}</p>
    <p>Mahasiswa: {{ $submission->student->name }}</p>
    <p>Nama berkas: {{ $submission->original_name }}</p>
    <p>Ukuran: {{ number_format($submission->file_size) }} byte</p>
    <p>Dikumpulkan: {{ $submission->submitted_at->format('d M Y H:i') }}</p>
    <p>Status: {{ $submission->is_late ? 'Terlambat' : 'Tepat waktu' }}</p>

    @if ($submission->note)
        <h3>Catatan mahasiswa</h3>
        <p>{{ $submission->note }}</p>
    @endif

    @if ($submission->grade)
        <h3>Nilai</h3>
        <p>{{ $submission->grade->score }}</p>
        @if ($submission->grade->feedback)
            <p>{{ $submission->grade->feedback }}</p>
        @endif
    @endif

    @if (auth()->user()->role === 'mahasiswa')
        <a href="{{ route('mahasiswa.assignments.show', $submission->assignment) }}">Kembali ke tugas</a>
    @else
        <a href="{{ route('dosen.assignments.show', $submission->assignment) }}">Kembali ke tugas</a>
    @endif
</x-layout>
