<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'KampusLMS' }}</title>
    @vite(['resources/css/app.css'])
</head>
<body>
    <nav>
        <a href="{{ url('/') }}">Dashboard</a>
        <a href="{{ route('courses.index') }}">Mata Kuliah</a>
        <a href="{{ route('users.index') }}">Pengguna</a>
        <a href="{{ route('tentang') }}">Tentang</a>
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