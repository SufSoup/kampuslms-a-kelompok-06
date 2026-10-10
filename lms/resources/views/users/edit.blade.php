<x-layout title="Edit Pengguna">
    <h1>Edit Pengguna</h1>

    @include('users._form', [
        'formAction' => route('users.update', $user),
        'method' => 'PUT',
    ])

    <a href="{{ route('users.show', $user) }}">Batal</a>
</x-layout>
