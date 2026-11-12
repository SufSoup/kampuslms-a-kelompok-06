<x-layout title="Tambah Pengguna — KampusLMS">
    <div class="breadcrumb">
        <a href="{{ route('admin.users.index') }}"><x-icon name="arrow-left" class="icon-sm" /> Daftar Pengguna</a>
    </div>

    <div class="page-header">
        <h1 class="page-title">Tambah Pengguna</h1>
        <p class="page-desc">Buat akun baru dengan role tertentu.</p>
    </div>

    <div class="card" style="max-width: 560px;">
        @include('users._form', [
            'formAction' => route('admin.users.store'),
            'method' => 'POST',
        ])
    </div>

    <p style="margin-top: 20px;">
        <a href="{{ route('admin.users.index') }}" class="link-muted"><x-icon name="arrow-left" class="icon-sm" /> Kembali</a>
    </p>
</x-layout>
