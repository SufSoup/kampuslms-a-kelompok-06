<x-layout title="Masuk — KampusLMS">
    <div class="auth-box">
        <div class="text-center" style="margin-bottom: 24px;">
            <h1 class="page-title">Masuk</h1>
            <p class="page-desc">Gunakan akun terdaftar untuk mengakses portal KampusLMS.</p>
        </div>

        <div class="auth-card">
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <x-icon name="alert-circle" class="icon" />
                    <div>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="form">
                @csrf

                <div class="field">
                    <label for="email">Alamat Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        placeholder="nama@kampuslms.test"
                    >
                </div>

                <div class="field">
                    <label for="password">Kata Sandi</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                        required
                        placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                    >
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                    <label class="checkbox-row" style="margin: 0;">
                        <input type="checkbox" name="remember" value="1">
                        Ingat sesi saya
                    </label>
                    <a href="{{ route('password.request') }}" style="font-size: 14px;">
                        Lupa kata sandi?
                    </a>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Masuk ke Akun</button>
            </form>
        </div>

        <!-- Akun Demo untuk Kemudahan Walkthrough UTS -->
        <div class="card" style="margin-top: 24px;">
            <p class="help-text" style="font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">
                Akun Demo (UTS / Milestone M2)
            </p>
            <div class="demo-list">
                <button type="button" class="demo-btn" onclick="fillLogin('admin@kampuslms.test', 'password')">
                    <x-icon name="shield" class="icon" />
                    <span><span class="badge badge-admin">Admin</span> &mdash; admin@kampuslms.test</span>
                </button>
                <button type="button" class="demo-btn" onclick="fillLogin('dosen@kampuslms.test', 'password')">
                    <x-icon name="users" class="icon" />
                    <span><span class="badge badge-dosen">Dosen</span> &mdash; dosen@kampuslms.test</span>
                </button>
                <button type="button" class="demo-btn" onclick="fillLogin('mahasiswa@kampuslms.test', 'password')">
                    <x-icon name="graduation" class="icon" />
                    <span><span class="badge badge-mahasiswa">Mahasiswa</span> &mdash; mahasiswa@kampuslms.test</span>
                </button>
            </div>
            <p class="help-text" style="margin-top: 10px;">Klik untuk isi otomatis. Password bawaan semua akun: <code>password</code></p>
        </div>
    </div>

    <script>
        function fillLogin(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }
    </script>
</x-layout>
