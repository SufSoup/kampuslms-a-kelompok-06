<x-layout title="Edit Pengguna — KampusLMS">
    <div class="breadcrumb">
        <a href="{{ route('admin.users.index') }}"><x-icon name="arrow-left" class="icon-sm" /> Daftar Pengguna</a>
        <x-icon name="chevron-right" class="icon-sm" />
        <span>{{ $user->name }}</span>
    </div>

    <div class="page-header">
        <h1 class="page-title">Edit Pengguna</h1>
        <p class="page-desc">Perbarui informasi akun {{ $user->name }}.</p>
    </div>

    <div class="card" style="max-width: 560px;">
        @include('users._form', [
            'formAction' => route('admin.users.update', $user),
            'method' => 'PUT',
        ])
    </div>

    <p style="margin-top: 20px;">
        <a href="{{ route('admin.users.show', $user) }}" class="link-muted"><x-icon name="arrow-left" class="icon-sm" /> Batal</a>
    </p>
</x-layout>
