<x-layout title="Edit Tugas — KampusLMS">
    <div class="breadcrumb">
        <a href="{{ route('dosen.assignments.index') }}"><x-icon name="arrow-left" class="icon-sm" /> Daftar Tugas</a>
        <x-icon name="chevron-right" class="icon-sm" />
        <span>Edit</span>
    </div>

    <div class="page-header">
        <h1 class="page-title">Edit Tugas</h1>
        <p class="page-desc">Mata kuliah: {{ $assignment->course->name }} ({{ $assignment->course->code }})</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <x-icon name="alert-circle" class="icon" />
            <div>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="card" style="max-width: 640px;">
        <form method="POST" action="{{ route('dosen.assignments.update', $assignment) }}" class="form">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="title">Judul</label>
                <input id="title" name="title" type="text" class="input {{ $errors->has('title') ? 'is-invalid' : '' }}" value="{{ old('title', $assignment->title) }}" maxlength="255" required>
            </div>

            <div class="field">
                <label for="instructions">Instruksi</label>
                <textarea id="instructions" name="instructions" class="input {{ $errors->has('instructions') ? 'is-invalid' : '' }}" required>{{ old('instructions', $assignment->instructions) }}</textarea>
            </div>

            <div class="field">
                <label for="due_at">Batas pengumpulan</label>
                <input id="due_at" name="due_at" type="datetime-local" class="input {{ $errors->has('due_at') ? 'is-invalid' : '' }}" value="{{ old('due_at', $assignment->due_at->format('Y-m-d\TH:i')) }}" required>
            </div>

            <div class="field">
                <label for="max_score">Nilai maksimum</label>
                <input id="max_score" name="max_score" type="number" min="1" max="100" class="input {{ $errors->has('max_score') ? 'is-invalid' : '' }}" value="{{ old('max_score', $assignment->max_score) }}" required>
            </div>

            <div class="field">
                <label for="allow_late">Pengumpulan terlambat</label>
                <select id="allow_late" name="allow_late" class="input" required>
                    <option value="1" @selected((string) old('allow_late', (int) $assignment->allow_late) === '1')>Diizinkan</option>
                    <option value="0" @selected((string) old('allow_late', (int) $assignment->allow_late) === '0')>Tidak diizinkan</option>
                </select>
            </div>

            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" class="input" required>
                    <option value="draft" @selected(old('status', $assignment->status) === 'draft')>Draft</option>
                    <option value="published" @selected(old('status', $assignment->status) === 'published')>Dipublikasikan</option>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <x-icon name="check" class="icon-sm" /> Simpan Perubahan
                </button>
                <a href="{{ route('dosen.assignments.show', $assignment) }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</x-layout>
