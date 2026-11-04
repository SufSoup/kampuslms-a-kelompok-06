<x-layout title="Monitoring Pengumpulan - {{ $assignment->title }}">
    <h1>Monitoring Pengumpulan</h1>
    <h2>{{ $assignment->title }}</h2>
    <p>Mata kuliah: {{ $assignment->course->name }} ({{ $assignment->course->code }})</p>

    <section aria-label="Ringkasan pengumpulan">
        <p>Mahasiswa terdaftar: {{ $registeredCount }}</p>
        <p>Sudah mengumpulkan: {{ $submittedCount }}</p>
        <p>Belum mengumpulkan: {{ $notSubmittedCount }}</p>
    </section>

    @if ($registeredCount === 0)
        <p>Belum ada mahasiswa yang terdaftar pada mata kuliah ini.</p>
    @else
        <table>
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
                        <td>{{ $student->name }}</td>
                        @if ($submission)
                            <td>Sudah mengumpulkan</td>
                            <td>{{ $submission->original_name }}</td>
                            <td>{{ $submission->formatted_file_size }}</td>
                            <td>{{ $submission->submitted_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td>{{ $submission->is_late ? 'Terlambat' : 'Tepat waktu' }}</td>
                            <td>{{ $submission->grade?->score ?? 'Belum dinilai' }}</td>
                            <td>
                                <a href="{{ route('submissions.download', $submission) }}">Download</a>
                                <a href="{{ route('dosen.submissions.show', $submission) }}">Detail</a>
                                <a href="{{ route('dosen.submissions.grade.edit', $submission) }}">
                                    {{ $submission->grade ? 'Ubah nilai' : 'Beri nilai' }}
                                </a>
                            </td>
                        @else
                            <td>Belum mengumpulkan</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $students->links() }}
    @endif

    <p><a href="{{ route('dosen.assignments.show', $assignment) }}">Kembali ke detail tugas</a></p>
</x-layout>
