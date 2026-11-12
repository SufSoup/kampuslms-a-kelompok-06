<x-layout title="Penilaian — {{ $submission->assignment->title }}">
    <div class="breadcrumb">
        <a href="{{ route('dosen.assignments.submissions.index', $submission->assignment) }}">
            <x-icon name="arrow-left" class="icon-sm" /> Monitoring Pengumpulan
        </a>
    </div>

    <div class="page-header">
        <h1 class="page-title">Penilaian Tugas</h1>
        <p class="page-desc">{{ $submission->assignment->title }} &mdash; {{ $submission->assignment->course->name }} ({{ $submission->assignment->course->code }})</p>
    </div>

    <div class="profile-card">
        <div class="detail-grid">
            <div class="detail-item">
                <p class="detail-label">Mahasiswa</p>
                <p class="detail-value">{{ $submission->student->name }}</p>
            </div>
            <div class="detail-item">
                <p class="detail-label">Nilai Maksimum</p>
                <p class="detail-value">{{ $submission->assignment->max_score }}</p>
            </div>
            <div class="detail-item">
                <p class="detail-label">Status</p>
                <p class="detail-value">
                    @if ($submission->grade)
                        <span class="badge badge-primary">Sudah dinilai</span>
                    @else
                        <span class="badge badge-warning">Belum dinilai</span>
                    @endif
                </p>
            </div>
        </div>
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
        <form method="POST" action="{{ route('dosen.submissions.grade.store', $submission) }}" class="form">
            @csrf
            <div class="field">
                <label for="score">Nilai (0&ndash;{{ $submission->assignment->max_score }})</label>
                <input
                    type="number"
                    id="score"
                    name="score"
                    class="input {{ $errors->has('score') ? 'is-invalid' : '' }}"
                    value="{{ old('score', $submission->grade?->score) }}"
                    min="0"
                    max="{{ $submission->assignment->max_score }}"
                    step="0.01"
                    required
                >
                @error('score')
                    <p class="field-error" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <div class="field">
                <label for="feedback">Feedback dosen (opsional)</label>
                <textarea id="feedback" name="feedback" rows="5" class="input {{ $errors->has('feedback') ? 'is-invalid' : '' }}">{{ old('feedback', $submission->grade?->feedback) }}</textarea>
                @error('feedback')
                    <p class="field-error" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <x-icon name="check" class="icon-sm" /> Simpan Nilai
                </button>
                <a href="{{ route('dosen.assignments.submissions.index', $submission->assignment) }}" class="btn btn-secondary">
                    Kembali ke monitoring
                </a>
            </div>
        </form>
    </div>
</x-layout>
