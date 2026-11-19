<x-layout title="Daftar Tugas — KampusLMS">
    <div class="page-header page-header-row">
        <div>
            <h1 class="page-title">Daftar Tugas</h1>
            <p class="page-desc">
                @if (auth()->user()->role === 'dosen')
                    Kelola tugas pada mata kuliah yang Anda ampu.
                @else
                    Semua tugas dari mata kuliah yang Anda ikuti.
                @endif
            </p>
        </div>

        @if (auth()->user()->role === 'dosen' && $courses->isNotEmpty())
            <div class="item-actions">
                @foreach ($courses as $course)
                    <a href="{{ route('dosen.courses.assignments.create', $course) }}" class="btn btn-primary">
                        <x-icon name="plus" class="icon-sm" /> Tugas {{ $course->name }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($assignments->isEmpty())
        <div class="empty-state">
            <x-icon name="clipboard" class="icon" />
            <span>
                @if (auth()->user()->role === 'dosen' && $courses->isEmpty())
                    Anda belum memiliki mata kuliah yang diampu.
                @else
                    Belum ada tugas yang dapat ditampilkan.
                @endif
            </span>
        </div>
    @else
        <div class="table-card">
            <div class="table-wrap">
                <table class="data-table">
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
                                <td class="cell-strong">{{ $assignment->title }}</td>
                                <td class="cell-muted">{{ $assignment->course->name }}</td>
                                <td class="cell-muted">{{ $assignment->due_at->format('d M Y H:i') }}</td>
                                <td>
                                    <span class="badge {{ $assignment->status === 'published' ? 'badge-success' : 'badge-warning' }}">
                                        {{ ucfirst($assignment->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="cell-actions">
                                        @if (auth()->user()->role === 'dosen')
                                            <a href="{{ route('dosen.assignments.show', $assignment) }}" class="btn btn-secondary btn-sm">Detail</a>
                                        @else
                                            <a href="{{ route('mahasiswa.assignments.show', $assignment) }}" class="btn btn-secondary btn-sm">Detail</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination-wrapper">
            {{ $assignments->links() }}
        </div>
    @endif
</x-layout>
