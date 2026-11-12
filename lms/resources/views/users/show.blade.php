<x-layout title="Detail Pengguna — KampusLMS">
    <div class="breadcrumb">
        <a href="{{ route('admin.users.index') }}"><x-icon name="arrow-left" class="icon-sm" /> Daftar Pengguna</a>
        <x-icon name="chevron-right" class="icon-sm" />
        <span>{{ $user->name }}</span>
    </div>

    <div class="page-header page-header-row">
        <div>
            <h1 class="page-title">{{ $user->name }}</h1>
            <p class="page-desc">Informasi lengkap akun pengguna.</p>
        </div>
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">
            <x-icon name="edit" class="icon-sm" /> Edit Pengguna
        </a>
    </div>

    <div class="profile-card">
        <div class="profile-top">
            <span class="badge badge-{{ $user->role }}">{{ ucfirst($user->role) }}</span>
        </div>
        <div class="detail-grid">
            <div class="detail-item">
                <p class="detail-label">Nama</p>
                <p class="detail-value">{{ $user->name }}</p>
            </div>
            <div class="detail-item">
                <p class="detail-label">Email</p>
                <p class="detail-value">{{ $user->email }}</p>
            </div>
            <div class="detail-item">
                <p class="detail-label">Role</p>
                <p class="detail-value">{{ ucfirst($user->role) }}</p>
            </div>
            <div class="detail-item">
                <p class="detail-label">NIM/NIP</p>
                <p class="detail-value">{{ $user->nim_nip ?? '-' }}</p>
            </div>
        </div>
    </div>

    <p>
        <a href="{{ route('admin.users.index') }}" class="link-muted"><x-icon name="arrow-left" class="icon-sm" /> Kembali ke daftar</a>
    </p>
</x-layout>
