<x-layout title="404 — Halaman Tidak Ditemukan">
    <div class="empty-state" style="margin-top: 60px;">
        <span class="stat-label">Error</span>
        <p class="profile-title" style="margin: 0;">404</p>
        <span>Halaman yang kamu cari tidak ditemukan.</span>
        <a href="{{ url('/') }}" class="btn btn-primary" style="margin-top: 8px;">
            <x-icon name="home" class="icon-sm" /> Kembali ke beranda
        </a>
    </div>
</x-layout>
