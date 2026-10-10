<x-layout title="Edit Pengguna">
    <h1>Edit Pengguna</h1>

    @include('users._form', [
        'formAction' => route('admin.users.update', $user),
        'method' => 'PUT',
    ])

    <a href="{{ route('admin.users.show', $user) }}">Batal</a>
</x-layout>
