<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'KampusLMS' }}</title>
    @vite(['resources/css/app.css'])
</head>
<body>
    <nav style="display: flex; justify-content: space-between; align-items: center; padding: 16px 48px; background: #F5F5F7; border-bottom: 1px solid #E0E0E0;">
        <div style="display: flex; gap: 24px; align-items: center;">
            <a href="{{ url('/') }}" style="font-weight: 700; font-size: 16px;">KampusLMS</a>
            <a href="{{ route('tentang') }}">Tentang</a>

            @auth
                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('admin.courses.index') }}">Mata Kuliah</a>
                    <a href="{{ route('admin.users.index') }}">Pengguna</a>
                @elseif (auth()->user()->role === 'dosen')
                    <a href="{{ route('dosen.courses.index') }}">Mata Kuliah</a>
                @elseif (auth()->user()->role === 'mahasiswa')
                    <a href="{{ route('mahasiswa.courses.index') }}">Mata Kuliah</a>
                @endif
                <a href="{{ route('dashboard') }}">Dashboard</a>
            @endauth
        </div>

        <div style="display: flex; gap: 16px; align-items: center;">
            @auth
                <div style="display: flex; align-items: center; gap: 10px; font-size: 14px;">
                    <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; background: #E5E5EA; color: #1D1D1F;">
                        {{ auth()->user()->role }}
                    </span>
                    <span style="font-weight: 500;">{{ auth()->user()->name }}</span>
                </div>

                <form method="POST" action="{{ route('logout') }}" style="margin: 0; display: inline;">
                    @csrf
                    <button type="submit" style="background: transparent; color: #FF3B30; border: 1px solid #FF3B30; padding: 6px 14px; border-radius: 980px; font-size: 13px;">
                        Keluar
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" style="display: inline-block; background: #000000; color: #FFFFFF; border-radius: 980px; padding: 8px 20px; font-size: 14px; text-decoration: none; border-bottom: none;">
                    Masuk
                </a>
            @endauth
        </div>
    </nav>

    <main>
        @if (session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger" role="alert">
                {{ session('error') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning" role="alert">
                {{ session('warning') }}
            </div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>