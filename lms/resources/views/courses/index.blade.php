<x-layout title="Daftar Mata Kuliah">
    <h1>Daftar Mata Kuliah</h1>

    <div class="action-header">
        <a href="{{ route('courses.create') }}">Tambah Mata Kuliah</a>

        <form method="GET" action="{{ route('courses.index') }}" class="filter-bar">
            <input
                type="text"
                name="q"
                placeholder="Cari kode atau nama..."
                value="{{ request('q') }}"
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
                <a href="{{ route('courses.index') }}" class="btn-reset">Reset</a>
            @endif
        </form>
    </div>

    @if ($courses->isEmpty())
        <p class="empty-state">Tidak ada mata kuliah yang ditemukan.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
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
                            <a href="{{ route('courses.show', $course) }}">{{ $course->name }}</a>
                        </td>
                        <td>{{ $course->sks }}</td>
                        <td>
                            <span class="badge badge-{{ $course->status }}">
                                {{ ucfirst($course->status) }}
                            </span>
                        </td>
                        <td>{{ $course->lecturer?->name ?? 'Dosen tidak tersedia' }}</td>
                        <td>
                            <a href="{{ route('courses.edit', $course) }}">Edit</a>
                            <form method="POST" action="{{ route('courses.destroy', $course) }}" style="display: inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination-wrapper">
            {{ $courses->links() }}
        </div>
    @endif
</x-layout>