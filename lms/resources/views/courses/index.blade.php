<x-layout title="Daftar Mata Kuliah — KampusLMS">
    @php
        $courseIndexRoute = match (auth()->user()->role) {
            'admin' => 'admin.courses.index',
            'dosen' => 'dosen.courses.index',
            'mahasiswa' => 'mahasiswa.courses.index',
        };

        $courseShowRoute = match (auth()->user()->role) {
            'admin' => 'admin.courses.show',
            'dosen' => 'dosen.courses.show',
            'mahasiswa' => 'mahasiswa.courses.show',
        };
    @endphp

    <div class="page-header page-header-row">
        <div>
            <h1 class="page-title">Daftar Mata Kuliah</h1>
            <p class="page-desc">Jelajahi mata kuliah yang tersedia pada sistem.</p>
        </div>

        @can('create', \App\Models\Course::class)
            <a href="{{ route('admin.courses.create') }}" class="btn btn-primary">
                <x-icon name="plus" class="icon-sm" /> Tambah Mata Kuliah
            </a>
        @endcan
    </div>

    <form method="GET" action="{{ route($courseIndexRoute) }}" class="filter-bar" style="margin-bottom: 20px;">
        <input
            type="text"
            name="q"
            class="input"
            value="{{ request('q') }}"
            placeholder="Cari mata kuliah..."
        >

        <select name="status" class="input">
            <option value="">Semua Status</option>
            @foreach (['draft', 'active', 'archived'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>
                    {{ ucfirst($status) }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-secondary btn-sm">
            <x-icon name="search" class="icon-sm" /> Cari
        </button>

        @if (request()->hasAny(['q', 'status']))
            <a href="{{ route($courseIndexRoute) }}" class="btn-reset">Reset</a>
        @endif
    </form>

    @if ($courses->isEmpty())
        <div class="empty-state">
            <x-icon name="search" class="icon" />
            <span>Belum ada mata kuliah yang tersedia.</span>
        </div>
    @else
        <div class="table-card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Mata Kuliah</th>
                            <th>SKS</th>
                            <th>Status</th>
                            <th>Dosen</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($courses as $course)
                            <tr>
                                <td><span class="chip">{{ $course->code }}</span></td>
                                <td class="cell-strong">
                                    <a class="link-table" href="{{ route($courseShowRoute, $course) }}">{{ $course->name }}</a>
                                </td>
                                <td class="cell-muted">{{ $course->sks }}</td>
                                <td>
                                    <span class="badge badge-{{ $course->status === 'active' ? 'success' : ($course->status === 'draft' ? 'warning' : 'neutral') }}">
                                        {{ ucfirst($course->status) }}
                                    </span>
                                </td>
                                <td class="cell-muted">{{ $course->lecturer?->name ?? 'Dosen tidak tersedia' }}</td>
                                <td>
                                    <div class="cell-actions">
                                        @can('update', $course)
                                            <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-secondary btn-sm">
                                                <x-icon name="edit" class="icon-sm" /> Edit
                                            </a>
                                        @endcan

                                        @can('delete', $course)
                                            <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <x-icon name="trash" class="icon-sm" /> Hapus
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination-wrapper">
            {{ $courses->links() }}
        </div>
    @endif
</x-layout>
