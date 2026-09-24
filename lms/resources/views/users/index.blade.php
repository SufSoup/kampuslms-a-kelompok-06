<x-layout title="Daftar Pengguna">
    <h1>Daftar Pengguna</h1>

    <div class="action-header">
        <a href="{{ route('users.create') }}">Tambah Pengguna</a>

        <form method="GET" action="{{ route('users.index') }}" class="filter-bar">
            <input
                type="text"
                name="q"
                placeholder="Cari nama atau email..."
                value="{{ request('q') }}"
            >
            <select name="role">
                <option value="">Semua Role</option>
                @foreach (['admin', 'dosen', 'mahasiswa'] as $role)
                    <option value="{{ $role }}" @selected(request('role') === $role)>
                        {{ ucfirst($role) }}
                    </option>
                @endforeach
            </select>
            <button type="submit">Cari</button>
            @if (request()->hasAny(['q', 'role']))
                <a href="{{ route('users.index') }}" class="btn-reset">Reset</a>
            @endif
        </form>
    </div>

    @if ($users->isEmpty())
        <p class="empty-state">Tidak ada pengguna yang ditemukan.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>NIM/NIP</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td><a href="{{ route('users.show', $user) }}">{{ $user->name }}</a></td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge badge-{{ $user->role }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td>{{ $user->nim_nip ?? '-' }}</td>
                        <td>
                            <a href="{{ route('users.edit', $user) }}">Edit</a>
                            <form method="POST" action="{{ route('users.destroy', $user) }}" style="display: inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
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
            {{ $users->links() }}
        </div>
    @endif
</x-layout>
