<x-layout title="Rekap Nilai - {{ $course->name }}">
    <h1>Rekap Nilai</h1>
    <h2>{{ $course->name }} ({{ $course->code }})</h2>
    <p>Dosen pengampu: {{ $course->lecturer?->name ?? '-' }}</p>

    @if (session('success'))
        <p role="status">{{ session('success') }}</p>
    @endif

    @if ($submissions->isEmpty())
        <p>Belum ada submission pada mata kuliah ini.</p>
    @else
        <table>
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
                        <td>{{ $submission->student->name }}</td>
                        <td>{{ $submission->assignment->title }}</td>
                        <td>
                            @if ($submission->grade)
                                {{ number_format((float) $submission->grade->score, 2) }} / {{ $submission->assignment->max_score }}
                            @else
                                - / {{ $submission->assignment->max_score }}
                            @endif
                        </td>
                        <td>{{ $submission->grade?->feedback ?? '-' }}</td>
                        <td>{{ $submission->grade ? 'Sudah dinilai' : 'Belum dinilai' }}</td>
                        <td>
                            <a href="{{ route('dosen.submissions.grade.edit', $submission) }}">
                                {{ $submission->grade ? 'Ubah nilai' : 'Beri nilai' }}
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $submissions->links() }}
    @endif

    <p><a href="{{ route('dosen.courses.show', $course) }}">Kembali ke mata kuliah</a></p>
</x-layout>
