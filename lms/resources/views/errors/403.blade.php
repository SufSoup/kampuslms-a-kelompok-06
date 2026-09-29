<x-layout title="403 — Akses Ditolak">
    <div style="text-align: center; padding: 4rem 2rem;">
        <h1 style="font-size: 5rem; font-weight: bold; color: #dc2626; margin: 0;">403</h1>
        <h2 style="font-size: 1.5rem; margin: 0.5rem 0 1rem;">Akses Ditolak</h2>
        <p style="color: #6b7280; max-width: 420px; margin: 0 auto 2rem;">
            Anda tidak memiliki izin untuk mengakses halaman ini.
            Pastikan Anda sudah login dengan akun yang sesuai.
        </p>
        <a href="{{ url('/') }}" style="
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 0.375rem;
            text-decoration: none;
            font-weight: 500;
        ">Kembali ke Beranda</a>
    </div>
</x-layout>
