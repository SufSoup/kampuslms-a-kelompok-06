<x-layout title="Daftar Pengguna">
    <h1>Daftar Pengguna</h1>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    <a href="{{ route('users.create') }}">Tambah Pengguna</a>

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
                    <td>{{ $user->role }}</td>
                    <td>{{ $user->nim_nip }}</td>
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
</x-layout>
