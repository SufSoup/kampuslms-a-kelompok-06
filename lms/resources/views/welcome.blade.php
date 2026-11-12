<x-layout title="KampusLMS — Portal Pembelajaran">
    <div class="auth-box" style="max-width: 640px; text-align: center; margin-top: 40px;">
        <span class="brand-mark" style="width: 56px; height: 56px; margin-bottom: 20px;">
            <x-icon name="graduation" class="icon" />
        </span>
        <h1 class="page-title" style="font-size: 32px;">KampusLMS</h1>
        <p class="page-desc" style="max-width: 480px; margin-left: auto; margin-right: auto;">
            Portal Learning Management System untuk kelola mata kuliah, materi pembelajaran,
            dan tugas &mdash; bagi admin, dosen, maupun mahasiswa.
        </p>

        <div class="quick-links" style="justify-content: center; margin-top: 28px;">
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary">
                    <x-icon name="home" class="icon-sm" /> Buka Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary">
                    <x-icon name="logout" class="icon-sm" /> Masuk ke Portal
                </a>
            @endauth
            <a href="{{ route('tentang') }}" class="btn btn-secondary">
                <x-icon name="info" class="icon-sm" /> Tentang Kelompok
            </a>
        </div>
    </div>

    <div class="stat-grid" style="margin-top: 48px;">
        <div class="stat-card">
            <span class="stat-icon"><x-icon name="book" class="icon" /></span>
            <div>
                <p class="stat-label">Mata Kuliah</p>
                <p class="detail-value text-secondary" style="font-size: 14px;">Kelola dan ikuti kelas</p>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-icon tone-purple"><x-icon name="file-text" class="icon" /></span>
            <div>
                <p class="stat-label">Materi</p>
                <p class="detail-value text-secondary" style="font-size: 14px;">Berkas &amp; tautan pembelajaran</p>
            </div>
        </div>
        <div class="stat-card">
            <span class="stat-icon tone-success"><x-icon name="clipboard" class="icon" /></span>
            <div>
                <p class="stat-label">Tugas</p>
                <p class="detail-value text-secondary" style="font-size: 14px;">Pengumpulan &amp; monitoring</p>
            </div>
        </div>
    </div>
</x-layout>
