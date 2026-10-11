<x-layout title="Buat Tugas">
    <h1>Buat Tugas</h1>
    <p>Mata kuliah: {{ $course->name }} ({{ $course->code }})</p>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('dosen.courses.assignments.store', $course) }}">
        @csrf
        <label for="title">Judul</label>
        <input id="title" name="title" type="text" value="{{ old('title') }}" maxlength="255" required>

        <label for="instructions">Instruksi</label>
        <textarea id="instructions" name="instructions" required>{{ old('instructions') }}</textarea>

        <label for="due_at">Batas pengumpulan</label>
        <input id="due_at" name="due_at" type="datetime-local" value="{{ old('due_at') }}" required>

        <label for="max_score">Nilai maksimum</label>
        <input id="max_score" name="max_score" type="number" min="1" max="100" value="{{ old('max_score', 100) }}" required>

        <label for="allow_late">Pengumpulan terlambat</label>
        <select id="allow_late" name="allow_late" required>
            <option value="1" @selected(old('allow_late', '1') === '1')>Diizinkan</option>
            <option value="0" @selected(old('allow_late') === '0')>Tidak diizinkan</option>
        </select>

        <label for="status">Status</label>
        <select id="status" name="status" required>
            <option value="draft" @selected(old('status') === 'draft')>Draft</option>
            <option value="published" @selected(old('status', 'published') === 'published')>Dipublikasikan</option>
        </select>

        <button type="submit">Simpan Tugas</button>
        <a href="{{ route('dosen.assignments.index') }}">Batal</a>
    </form>
</x-layout>
