<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="KampusLMS — portal Learning Management System untuk kelola mata kuliah, materi, dan tugas.">
    <title>{{ $title ?? 'KampusLMS' }}</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect width='24' height='24' rx='6' fill='%230071E3'/%3E%3Cpath d='M19 11.5 12 8l-7 3.5 7 3.5 7-3.5z' fill='%23ffffff'/%3E%3Cpath d='M9.5 13.9V16c0 1.25 1.9 2.25 2.5 2.25s2.5-1 2.5-2.25v-2.1L12 14.8z' fill='%23ffffff'/%3E%3C/svg%3E">
    @vite(['resources/css/app.css'])
</head>
<body>
    <header class="app-navbar">
        <div class="navbar-inner">
            <a href="{{ url('/') }}" class="brand">
                <span class="brand-mark"><x-icon name="graduation" class="icon" /></span>
                KampusLMS
            </a>

            <nav class="nav-links" aria-label="Navigasi utama">
                @auth
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <x-icon name="home" class="icon-sm" /> Dashboard
                    </a>

                    @if (auth()->user()->role === 'admin')
                        <a href="{{ route('admin.courses.index') }}" class="nav-link {{ request()->routeIs('admin.courses.*', 'admin.materials.*') ? 'active' : '' }}">
                            <x-icon name="book" class="icon-sm" /> Mata Kuliah
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <x-icon name="users" class="icon-sm" /> Pengguna
                        </a>
                    @elseif (auth()->user()->role === 'dosen')
                        <a href="{{ route('dosen.courses.index') }}" class="nav-link {{ request()->routeIs('dosen.courses.*', 'dosen.materials.*') ? 'active' : '' }}">
                            <x-icon name="book" class="icon-sm" /> Mata Kuliah
                        </a>
                        <a href="{{ route('dosen.assignments.index') }}" class="nav-link {{ request()->routeIs('dosen.assignments.*', 'dosen.submissions.*') ? 'active' : '' }}">
                            <x-icon name="clipboard" class="icon-sm" /> Tugas
                        </a>
                    @elseif (auth()->user()->role === 'mahasiswa')
                        <a href="{{ route('mahasiswa.courses.index') }}" class="nav-link {{ request()->routeIs('mahasiswa.courses.*', 'mahasiswa.materials.*') ? 'active' : '' }}">
                            <x-icon name="book" class="icon-sm" /> Mata Kuliah
                        </a>
                        <a href="{{ route('mahasiswa.assignments.index') }}" class="nav-link {{ request()->routeIs('mahasiswa.assignments.*', 'mahasiswa.submissions.*') ? 'active' : '' }}">
                            <x-icon name="clipboard" class="icon-sm" /> Tugas
                        </a>
                    @endif
                @endauth

                <a href="{{ route('tentang') }}" class="nav-link {{ request()->routeIs('tentang') ? 'active' : '' }}">
                    <x-icon name="info" class="icon-sm" /> Tentang
                </a>
            </nav>

            <div class="navbar-right">
                @auth
                    @php($unreadNotificationCount = auth()->user()->unreadNotifications()->count())
                    <a href="{{ route('notifications.index') }}" class="nav-bell"
                        aria-label="Notifikasi, {{ $unreadNotificationCount }} belum dibaca">
                        <x-icon name="bell" class="icon" />
                        <span class="bell-badge" data-notification-unread-count="{{ $unreadNotificationCount }}" aria-live="polite" @if ($unreadNotificationCount === 0) hidden @endif>
                            {{ $unreadNotificationCount }}
                        </span>
                    </a>

                    <div class="user-meta">
                        <span class="badge badge-{{ auth()->user()->role }}">{{ ucfirst(auth()->user()->role) }}</span>
                        <span class="user-name">{{ auth()->user()->name }}</span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm">
                            <x-icon name="logout" class="icon-sm" /> Keluar
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary btn-sm">Masuk</a>
                @endauth

                <button type="button" class="nav-toggle" id="nav-toggle" aria-expanded="false" aria-controls="mobile-menu" aria-label="Buka menu navigasi">
                    <x-icon name="menu" class="icon" data-icon-open />
                    <x-icon name="x" class="icon" data-icon-close hidden />
                </button>
            </div>
        </div>

        <nav class="mobile-menu" id="mobile-menu" aria-label="Navigasi mobile">
            @auth
                <a href="{{ route('dashboard') }}" class="mobile-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <x-icon name="home" class="icon-sm" /> Dashboard
                </a>
                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('admin.courses.index') }}" class="mobile-link {{ request()->routeIs('admin.courses.*', 'admin.materials.*') ? 'active' : '' }}">
                        <x-icon name="book" class="icon-sm" /> Mata Kuliah
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="mobile-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <x-icon name="users" class="icon-sm" /> Pengguna
                    </a>
                @elseif (auth()->user()->role === 'dosen')
                    <a href="{{ route('dosen.courses.index') }}" class="mobile-link {{ request()->routeIs('dosen.courses.*', 'dosen.materials.*') ? 'active' : '' }}">
                        <x-icon name="book" class="icon-sm" /> Mata Kuliah
                    </a>
                    <a href="{{ route('dosen.assignments.index') }}" class="mobile-link {{ request()->routeIs('dosen.assignments.*', 'dosen.submissions.*') ? 'active' : '' }}">
                        <x-icon name="clipboard" class="icon-sm" /> Tugas
                    </a>
                @elseif (auth()->user()->role === 'mahasiswa')
                    <a href="{{ route('mahasiswa.courses.index') }}" class="mobile-link {{ request()->routeIs('mahasiswa.courses.*', 'mahasiswa.materials.*') ? 'active' : '' }}">
                        <x-icon name="book" class="icon-sm" /> Mata Kuliah
                    </a>
                    <a href="{{ route('mahasiswa.assignments.index') }}" class="mobile-link {{ request()->routeIs('mahasiswa.assignments.*', 'mahasiswa.submissions.*') ? 'active' : '' }}">
                        <x-icon name="clipboard" class="icon-sm" /> Tugas
                    </a>
                @endif
                <div class="mobile-divider"></div>
                <a href="{{ route('notifications.index') }}" class="mobile-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                    <x-icon name="bell" class="icon-sm" /> Notifikasi
                    @if (($unreadNotificationCount ?? 0) > 0)
                        <span class="badge badge-danger">{{ $unreadNotificationCount }}</span>
                    @endif
                </a>
            @endauth
            <a href="{{ route('tentang') }}" class="mobile-link {{ request()->routeIs('tentang') ? 'active' : '' }}">
                <x-icon name="info" class="icon-sm" /> Tentang
            </a>
        </nav>
    </header>

    <main class="container">
        @if (session('success'))
            <div class="alert alert-success" role="alert">
                <x-icon name="check-circle" class="icon" />
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger" role="alert">
                <x-icon name="alert-circle" class="icon" />
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning" role="alert">
                <x-icon name="alert-triangle" class="icon" />
                <div>{{ session('warning') }}</div>
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="app-footer">
        <div class="footer-inner">
            <span>&copy; {{ date('Y') }} KampusLMS — Kelompok 06 (Kelas A)</span>
            <a href="{{ route('tentang') }}">Tentang Kelompok</a>
        </div>
    </footer>

    <script>
        (function () {
            var toggle = document.getElementById('nav-toggle');
            var menu = document.getElementById('mobile-menu');
            if (!toggle || !menu) return;
            var openIcon = toggle.querySelector('[data-icon-open]');
            var closeIcon = toggle.querySelector('[data-icon-close]');
            toggle.addEventListener('click', function () {
                var open = menu.classList.toggle('open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                toggle.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');
                if (openIcon) openIcon.hidden = open;
                if (closeIcon) closeIcon.hidden = !open;
            });
        })();
    </script>

    @auth
        <script>
            (() => {
                const badge = document.querySelector('[data-notification-unread-count]');

                if (!badge || window.kampusLmsNotificationPolling) {
                    return;
                }

                let failureLogged = false;
                const endpoint = @json(url('/api/v1/notifications?page=1'));

                const pollUnreadCount = async () => {
                    try {
                        const response = await fetch(endpoint, {
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'application/json',
                            },
                        });

                        if (!response.ok) {
                            if (!failureLogged) {
                                console.warn(`Polling notifikasi gagal dengan status ${response.status}.`);
                                failureLogged = true;
                            }

                            if (response.status === 401 || response.status === 403) {
                                window.clearInterval(window.kampusLmsNotificationPolling);
                                window.kampusLmsNotificationPolling = null;
                            }

                            return;
                        }

                        const payload = await response.json();
                        const unreadCount = payload?.meta?.unread_count;

                        if (!Number.isInteger(unreadCount) || unreadCount < 0) {
                            if (!failureLogged) {
                                console.warn('Polling notifikasi menerima format respons yang tidak sesuai.');
                                failureLogged = true;
                            }

                            return;
                        }

                        badge.textContent = String(unreadCount);
                        badge.dataset.notificationUnreadCount = String(unreadCount);
                        badge.hidden = unreadCount === 0;
                        failureLogged = false;
                    } catch (error) {
                        if (!failureLogged) {
                            console.warn('Polling notifikasi gagal karena masalah jaringan atau respons JSON.', error);
                            failureLogged = true;
                        }
                    }
                };

                window.kampusLmsNotificationPolling = window.setInterval(pollUnreadCount, 30_000);
            })();
        </script>
    @endauth
</body>
</html>
