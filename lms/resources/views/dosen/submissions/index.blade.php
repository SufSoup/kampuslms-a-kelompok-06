<x-layout title="Monitoring Pengumpulan — {{ $assignment->title }}">
    <div class="breadcrumb">
        <a href="{{ route('dosen.assignments.show', $assignment) }}"><x-icon name="arrow-left" class="icon-sm" /> Detail Tugas</a>
    </div>

    <div class="page-header">
        <h1 class="page-title">Monitoring Pengumpulan</h1>
        <p class="page-desc">{{ $assignment->title }} &mdash; {{ $assignment->course->name }} ({{ $assignment->course->code }})</p>
    </div>

    <section aria-label="Ringkasan pengumpulan" class="card" style="margin-bottom: 28px;">
        <div class="item-meta" style="margin: 0; font-size: 14px;">
            <span><x-icon name="users" class="icon-sm" /> Mahasiswa terdaftar: {{ $registeredCount }}</span>
            <span><x-icon name="check-circle" class="icon-sm" /> Sudah mengumpulkan: {{ $submittedCount }}</span>
            <span><x-icon name="clock" class="icon-sm" /> Belum mengumpulkan: {{ $notSubmittedCount }}</span>
        </div>
    </section>

    @if ($registeredCount === 0)
        <div class="empty-state">
            <x-icon name="users" class="icon" />
            <span>Belum ada mahasiswa yang terdaftar pada mata kuliah ini.</span>
        </div>
    @else
        <div class="table-card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Mahasiswa</th>
                            <th scope="col">Status Pengumpulan</th>
                            <th scope="col">Berkas</th>
                            <th scope="col">Ukuran</th>
                            <th scope="col">Waktu Pengumpulan</th>
                            <th scope="col">Ketepatan Waktu</th>
                            <th scope="col">Nilai</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            @php
                                $submission = $student->submissions->first();
                            @endphp
                            <tr>
                                <td class="cell-strong">{{ $student->name }}</td>
                                @if ($submission)
                                    <td><span class="badge badge-success">Sudah mengumpulkan</span></td>
                                    <td>{{ $submission->original_name }}</td>
                                    <td class="cell-muted">{{ $submission->formatted_file_size }}</td>
                                    <td class="cell-muted">{{ $submission->submitted_at?->format('d M Y H:i') ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ $submission->is_late ? 'badge-danger' : 'badge-neutral' }}">
                                            {{ $submission->is_late ? 'Terlambat' : 'Tepat waktu' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($submission->grade)
                                            <span class="badge badge-primary">{{ $submission->grade->score }}</span>
                                        @else
                                            <span class="badge badge-warning">Belum dinilai</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="cell-actions">
                                            <a href="{{ route('submissions.download', $submission) }}" class="btn btn-secondary btn-sm">
                                                <x-icon name="download" class="icon-sm" /> Download
                                            </a>
                                            <a href="{{ route('dosen.submissions.show', $submission) }}" class="btn btn-secondary btn-sm">Detail</a>
                                            <a href="{{ route('dosen.submissions.grade.edit', $submission) }}" class="btn btn-primary btn-sm">
                                                {{ $submission->grade ? 'Ubah nilai' : 'Beri nilai' }}
                                            </a>
                                        </div>
                                    </td>
                                @else
                                    <td><span class="badge badge-warning">Belum mengumpulkan</span></td>
                                    <td class="cell-muted">-</td>
                                    <td class="cell-muted">-</td>
                                    <td class="cell-muted">-</td>
                                    <td class="cell-muted">-</td>
                                    <td class="cell-muted">-</td>
                                    <td class="cell-muted">-</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination-wrapper">
            {{ $students->links() }}
        </div>
    @endif
</x-layout>
