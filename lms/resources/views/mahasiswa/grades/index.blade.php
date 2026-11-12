<x-layout title="Nilai Saya — KampusLMS">
    <div class="breadcrumb">
        <a href="{{ route('dashboard') }}"><x-icon name="arrow-left" class="icon-sm" /> Dashboard</a>
    </div>

    <div class="page-header">
        <h1 class="page-title">Nilai Saya</h1>
        <p class="page-desc">Daftar tugas yang telah Anda kumpulkan beserta nilai dan feedback.</p>
    </div>

    @if ($submissions->isEmpty())
        <div class="empty-state">
            <x-icon name="award" class="icon" />
            <span>Anda belum mengumpulkan tugas apa pun.</span>
        </div>
    @else
        <div class="table-card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Mata kuliah</th>
                            <th scope="col">Tugas</th>
                            <th scope="col">Nilai</th>
                            <th scope="col">Feedback</th>
                            <th scope="col">Tanggal penilaian</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($submissions as $submission)
                            <tr>
                                <td class="cell-muted">{{ $submission->assignment->course->name }}</td>
                                <td class="cell-strong">{{ $submission->assignment->title }}</td>
                                <td>
                                    @if ($submission->grade)
                                        <span class="badge badge-primary">{{ number_format((float) $submission->grade->score, 2) }} / {{ $submission->assignment->max_score }}</span>
                                    @else
                                        <span class="text-muted">- / {{ $submission->assignment->max_score }}</span>
                                    @endif
                                </td>
                                <td class="cell-muted" style="max-width: 280px;">{{ $submission->grade?->feedback ?? '-' }}</td>
                                <td class="cell-muted">{{ $submission->grade?->graded_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td>
                                    @if ($submission->grade)
                                        <span class="badge badge-success">Sudah dinilai</span>
                                    @else
                                        <span class="badge badge-warning">Belum dinilai</span>
                                    @endif
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
