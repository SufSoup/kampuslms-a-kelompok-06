<x-layout title="Rekap Nilai — {{ $course->name }}">
    <div class="breadcrumb">
        <a href="{{ route('dosen.courses.show', $course) }}"><x-icon name="arrow-left" class="icon-sm" /> {{ $course->name }}</a>
    </div>

    <div class="page-header">
        <h1 class="page-title">Rekap Nilai</h1>
        <p class="page-desc">
            {{ $course->name }} ({{ $course->code }}) &mdash; Dosen pengampu: {{ $course->lecturer?->name ?? '-' }}
        </p>
    </div>

    @if ($submissions->isEmpty())
        <div class="empty-state">
            <x-icon name="award" class="icon" />
            <span>Belum ada submission pada mata kuliah ini.</span>
        </div>
    @else
        <div class="table-card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Mahasiswa</th>
                            <th scope="col">Tugas</th>
                            <th scope="col">Nilai</th>
                            <th scope="col">Feedback</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($submissions as $submission)
                            <tr>
                                <td class="cell-strong">{{ $submission->student->name }}</td>
                                <td class="cell-muted">{{ $submission->assignment->title }}</td>
                                <td>
                                    @if ($submission->grade)
                                        <span class="badge badge-primary">{{ number_format((float) $submission->grade->score, 2) }} / {{ $submission->assignment->max_score }}</span>
                                    @else
                                        <span class="text-muted">- / {{ $submission->assignment->max_score }}</span>
                                    @endif
                                </td>
                                <td class="cell-muted" style="max-width: 280px;">{{ $submission->grade?->feedback ?? '-' }}</td>
                                <td>
                                    @if ($submission->grade)
                                        <span class="badge badge-success">Sudah dinilai</span>
                                    @else
                                        <span class="badge badge-warning">Belum dinilai</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('dosen.submissions.grade.edit', $submission) }}" class="btn btn-primary btn-sm">
                                        {{ $submission->grade ? 'Ubah nilai' : 'Beri nilai' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination-wrapper">
            {{ $submissions->links() }}
        </div>
    @endif
</x-layout>
