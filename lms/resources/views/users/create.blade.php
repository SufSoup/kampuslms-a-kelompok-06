<x-layout title="Tambah Pengguna">
    <h1>Tambah Pengguna</h1>

    @include('users._form', [
        'formAction' => route('users.store'),
        'method' => 'POST',
    ])

    <a href="{{ route('users.index') }}">Kembali</a>
</x-layout>
