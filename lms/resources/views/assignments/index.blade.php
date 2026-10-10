<x-layout title="Daftar Tugas">
    <h1>Daftar Tugas</h1>

    @if (auth()->user()->role === 'dosen')
        <div class="action-header">
            <span>Kelola tugas pada mata kuliah yang Anda ampu.</span>
            @if ($courses->isNotEmpty())
                <div>
                    @foreach ($courses as $course)
                        <a href="{{ route('dosen.courses.assignments.create', $course) }}">
                            + Tugas {{ $course->name }}
                        </a>
                    @endforeach
                </div>
            @else
                <p class="empty-state">Anda belum memiliki mata kuliah yang diampu.</p>
            @endif
        </div>
    @endif

    @if ($assignments->isEmpty())
        <p class="empty-state">Belum ada tugas yang dapat ditampilkan.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Tugas</th>
                    <th>Mata Kuliah</th>
                    <th>Batas Pengumpulan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($assignments as $assignment)
                    <tr>
                        <td>{{ $assignment->title }}</td>
                        <td>{{ $assignment->course->name }}</td>
                        <td>{{ $assignment->due_at->format('d M Y H:i') }}</td>
                        <td>{{ ucfirst($assignment->status) }}</td>
                        <td>
                            @if (auth()->user()->role === 'dosen')
                                <a href="{{ route('dosen.assignments.show', $assignment) }}">Detail</a>
                            @else
                                <a href="{{ route('mahasiswa.assignments.show', $assignment) }}">Detail</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination-wrapper">
            {{ $assignments->links() }}
        </div>
    @endif
</x-layout>
