<x-layout title="Daftar Pengguna — KampusLMS">
    <div class="page-header page-header-row">
        <div>
            <h1 class="page-title">Daftar Pengguna</h1>
            <p class="page-desc">Kelola akun admin, dosen, dan mahasiswa.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="icon-sm" /> Tambah Pengguna
        </a>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="filter-bar" style="margin-bottom: 20px;">
        <input
            type="text"
            name="q"
            class="input"
            placeholder="Cari nama atau email..."
            value="{{ request('q') }}"
        >
        <select name="role" class="input">
            <option value="">Semua Role</option>
            @foreach (['admin', 'dosen', 'mahasiswa'] as $role)
                <option value="{{ $role }}" @selected(request('role') === $role)>
                    {{ ucfirst($role) }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">
            <x-icon name="search" class="icon-sm" /> Cari
        </button>
        @if (request()->hasAny(['q', 'role']))
            <a href="{{ route('admin.users.index') }}" class="btn-reset">Reset</a>
        @endif
    </form>

    @if ($users->isEmpty())
        <div class="empty-state">
            <x-icon name="users" class="icon" />
            <span>Tidak ada pengguna yang ditemukan.</span>
        </div>
    @else
        <div class="table-card">
            <div class="table-wrap">
                <table class="data-table">
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
                                <td class="cell-strong">
                                    <a class="link-table" href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a>
                                </td>
                                <td class="cell-muted">{{ $user->email }}</td>
                                <td>
                                    <span class="badge badge-{{ $user->role }}">{{ ucfirst($user->role) }}</span>
                                </td>
                                <td class="cell-muted">{{ $user->nim_nip ?? '-' }}</td>
                                <td>
                                    <div class="cell-actions">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-secondary btn-sm">
                                            <x-icon name="edit" class="icon-sm" /> Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <x-icon name="trash" class="icon-sm" /> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination-wrapper">
            {{ $users->links() }}
        </div>
    @endif
</x-layout>
