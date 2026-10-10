<x-layout title="Edit Tugas">
    <h1>Edit Tugas</h1>
    <p>Mata kuliah: {{ $assignment->course->name }} ({{ $assignment->course->code }})</p>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('dosen.assignments.update', $assignment) }}">
        @csrf
        @method('PUT')

        <label for="title">Judul</label>
        <input id="title" name="title" type="text" value="{{ old('title', $assignment->title) }}" maxlength="255" required>

        <label for="instructions">Instruksi</label>
        <textarea id="instructions" name="instructions" required>{{ old('instructions', $assignment->instructions) }}</textarea>

        <label for="due_at">Batas pengumpulan</label>
        <input id="due_at" name="due_at" type="datetime-local" value="{{ old('due_at', $assignment->due_at->format('Y-m-d\TH:i')) }}" required>

        <label for="max_score">Nilai maksimum</label>
        <input id="max_score" name="max_score" type="number" min="1" max="100" value="{{ old('max_score', $assignment->max_score) }}" required>

        <label for="allow_late">Pengumpulan terlambat</label>
        <select id="allow_late" name="allow_late" required>
            <option value="1" @selected((string) old('allow_late', (int) $assignment->allow_late) === '1')>Diizinkan</option>
            <option value="0" @selected((string) old('allow_late', (int) $assignment->allow_late) === '0')>Tidak diizinkan</option>
        </select>

        <label for="status">Status</label>
        <select id="status" name="status" required>
            <option value="draft" @selected(old('status', $assignment->status) === 'draft')>Draft</option>
            <option value="published" @selected(old('status', $assignment->status) === 'published')>Dipublikasikan</option>
        </select>

        <button type="submit">Simpan Perubahan</button>
        <a href="{{ route('dosen.assignments.show', $assignment) }}">Batal</a>
    </form>
</x-layout>
