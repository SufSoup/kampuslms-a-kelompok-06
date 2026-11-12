<x-layout title="403 — Akses Ditolak">
    <div class="empty-state" style="margin-top: 60px;">
        <span class="stat-label">Error</span>
        <p class="profile-title" style="margin: 0; color: var(--c-danger);">403</p>
        <span><strong>Akses Ditolak.</strong> Anda tidak memiliki izin untuk mengakses halaman ini.</span>
        <span class="text-secondary" style="font-size: 13.5px;">Pastikan Anda sudah login dengan akun yang sesuai.</span>
        <a href="{{ url('/') }}" class="btn btn-primary" style="margin-top: 8px;">
            <x-icon name="home" class="icon-sm" /> Kembali ke Beranda
        </a>
    </div>
</x-layout>
