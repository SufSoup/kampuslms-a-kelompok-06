<x-layout title="Detail Pengguna">
    <h1>{{ $user->name }}</h1>

    <p>Email: {{ $user->email }}</p>
    <p>Role: {{ $user->role }}</p>
    <p>NIM/NIP: {{ $user->nim_nip }}</p>

    <a href="{{ route('admin.users.edit', $user) }}">Edit</a>
    <a href="{{ route('admin.users.index') }}">Kembali ke daftar</a>
</x-layout>
