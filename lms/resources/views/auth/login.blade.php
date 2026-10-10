<x-layout title="Masuk — KampusLMS">
    <div style="max-width: 480px; margin: 0 auto;">
        <h1 style="font-size: 36px; margin-bottom: 8px;">Masuk</h1>
        <p style="color: #6E6E73; margin-bottom: 32px;">Gunakan akun terdaftar untuk mengakses portal KampusLMS.</p>

        @if ($errors->any())
            <div style="background: #FEE2E2; border: 1px solid #FCA5A5; color: #991B1B; padding: 12px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 14px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" style="max-width: 100%;">
            @csrf

            <div>
                <label for="email">Alamat Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    placeholder="nama@kampuslms.test"
                >
            </div>

            <div>
                <label for="password">Kata Sandi</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    placeholder="••••••••"
                >
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 20px; margin-bottom: 24px;">
                <label style="display: inline-flex; align-items: center; gap: 8px; margin: 0; text-transform: none; font-size: 14px; font-weight: normal; cursor: pointer;">
                    <input type="checkbox" name="remember" value="1" style="width: auto; margin: 0;">
                    Ingat sesi saya
                </label>
            </div>

            <button type="submit" style="width: 100%; padding: 12px 24px; font-size: 16px;">
                Masuk ke Akun
            </button>
        </form>

        <!-- Akun Demo untuk Kemudahan Walkthrough UTS -->
        <div style="margin-top: 40px; padding: 20px; background: #F5F5F7; border-radius: 16px; border: 1px solid #E5E5EA;">
            <p style="font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6E6E73; margin-bottom: 12px;">
                Akun Demo Ujian (UTS / Milestone M2)
            </p>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <button
                    type="button"
                    onclick="fillLogin('admin@kampuslms.test', 'password')"
                    style="background: #FFFFFF; color: #1D1D1F; border: 1px solid #D1D1D6; text-align: left; padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 500;"
                >
                    👑 <strong>Admin:</strong> admin@kampuslms.test (Isi otomatis)
                </button>
                <button
                    type="button"
                    onclick="fillLogin('dosen@kampuslms.test', 'password')"
                    style="background: #FFFFFF; color: #1D1D1F; border: 1px solid #D1D1D6; text-align: left; padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 500;"
                >
                    👨‍🏫 <strong>Dosen:</strong> dosen@kampuslms.test (Isi otomatis)
                </button>
                <button
                    type="button"
                    onclick="fillLogin('mahasiswa@kampuslms.test', 'password')"
                    style="background: #FFFFFF; color: #1D1D1F; border: 1px solid #D1D1D6; text-align: left; padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 500;"
                >
                    🎓 <strong>Mahasiswa:</strong> mahasiswa@kampuslms.test (Isi otomatis)
                </button>
            </div>
            <small style="display: block; margin-top: 10px; color: #8E8E93;">Password bawaan semua akun: <code>password</code></small>
        </div>
    </div>

    <script>
        function fillLogin(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }
    </script>
</x-layout>
