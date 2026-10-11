<x-layout title="Daftar Mata Kuliah">
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

    <h1>Daftar Mata Kuliah</h1>

    <div class="action-header">
        @can('create', \App\Models\Course::class)
            <a href="{{ route('admin.courses.create') }}">Tambah Mata Kuliah</a>
        @endcan

        <form method="GET" action="{{ route($courseIndexRoute) }}" class="filter-bar">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari mata kuliah..."
            >

            <select name="status">
                <option value="">Semua Status</option>
                @foreach (['draft', 'active', 'archived'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>

            <button type="submit">Cari</button>

            @if (request()->hasAny(['q', 'status']))
                <a href="{{ route($courseIndexRoute) }}" class="btn-reset">Reset</a>
            @endif
        </form>
    </div>

    @if ($courses->isEmpty())
        <p>Belum ada mata kuliah yang tersedia.</p>
    @else
        <table>
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
                        <td>{{ $course->code }}</td>
                        <td>
                            <a href="{{ route($courseShowRoute, $course) }}">{{ $course->name }}</a>
                        </td>
                        <td>{{ $course->sks }}</td>
                        <td>
                            <span class="badge badge-{{ $course->status }}">
                                {{ ucfirst($course->status) }}
                            </span>
                        </td>
                        <td>{{ $course->lecturer?->name ?? 'Dosen tidak tersedia' }}</td>
                        <td>
                            @can('update', $course)
                                <a href="{{ route('admin.courses.edit', $course) }}">Edit</a>
                            @endcan

                            @can('delete', $course)
                                <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" style="display: inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit">Hapus</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $courses->links() }}
    @endif
</x-layout>
