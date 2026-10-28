<x-layout title="Penilaian - {{ $submission->assignment->title }}">
    <h1>Penilaian Tugas</h1>
    <p>Tugas: {{ $submission->assignment->title }}</p>
    <p>Mata kuliah: {{ $submission->assignment->course->name }} ({{ $submission->assignment->course->code }})</p>
    <p>Mahasiswa: {{ $submission->student->name }}</p>
    <p>Nilai maksimum: {{ $submission->assignment->max_score }}</p>

    @if ($errors->any())
        <div role="alert" style="background: #FEE2E2; border: 1px solid #FCA5A5; color: #991B1B; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('dosen.submissions.grade.store', $submission) }}" style="max-width: 600px;">
        @csrf
        <div style="margin-bottom: 16px;">
            <label for="score" style="display: block; font-weight: bold; margin-bottom: 4px;">Nilai (0–{{ $submission->assignment->max_score }}):</label>
            <input
                type="number"
                id="score"
                name="score"
                value="{{ old('score', $submission->grade?->score) }}"
                min="0"
                max="{{ $submission->assignment->max_score }}"
                step="0.01"
                required
                style="width: 100%; border: 1px solid #CBD5E1; border-radius: 6px; padding: 8px;"
            >
            @error('score')
                <p role="alert" style="color: #991B1B;">{{ $message }}</p>
            @enderror
        </div>
        <div style="margin-bottom: 16px;">
            <label for="feedback" style="display: block; font-weight: bold; margin-bottom: 4px;">Feedback dosen (opsional):</label>
            <textarea id="feedback" name="feedback" rows="5" style="width: 100%; border: 1px solid #CBD5E1; border-radius: 6px; padding: 8px;">{{ old('feedback', $submission->grade?->feedback) }}</textarea>
            @error('feedback')
                <p role="alert" style="color: #991B1B;">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" style="padding: 10px 20px; background: #0071E3; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
            Simpan Nilai
        </button>
        <a href="{{ route('dosen.assignments.submissions.index', $submission->assignment) }}">Kembali ke monitoring</a>
    </form>
</x-layout>
