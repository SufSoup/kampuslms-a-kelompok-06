<x-layout title="Nilai Saya">
    <h1>Nilai Saya</h1>
    <p>Daftar tugas yang telah Anda kumpulkan.</p>

    @if ($submissions->isEmpty())
        <p>Anda belum mengumpulkan tugas apa pun.</p>
    @else
        <table>
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
                        <td>{{ $submission->assignment->course->name }}</td>
                        <td>{{ $submission->assignment->title }}</td>
                        <td>
                            @if ($submission->grade)
                                {{ number_format((float) $submission->grade->score, 2) }} / {{ $submission->assignment->max_score }}
                            @else
                                - / {{ $submission->assignment->max_score }}
                            @endif
                        </td>
                        <td>{{ $submission->grade?->feedback ?? '-' }}</td>
                        <td>{{ $submission->grade?->graded_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td>{{ $submission->grade ? 'Sudah dinilai' : 'Belum dinilai' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $submissions->links() }}
    @endif

    <p><a href="{{ route('dashboard') }}">Kembali ke dashboard</a></p>
</x-layout>
