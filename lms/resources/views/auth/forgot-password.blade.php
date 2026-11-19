<x-layout title="Lupa Kata Sandi — KampusLMS">
    <div class="auth-box">
        <div class="text-center" style="margin-bottom: 24px;">
            <h1 class="page-title">Lupa Kata Sandi</h1>
            <p class="page-desc">
                Masukkan alamat email yang terdaftar. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi Anda.
            </p>
        </div>

        <div class="auth-card">
            @if (session('success'))
                <div class="alert alert-success" role="alert">
                    <x-icon name="check-circle" class="icon" />
                    <div>{{ session('success') }}</div>
                </div>
            @endif

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

            <form method="POST" action="{{ route('password.email') }}" class="form">
                @csrf

                <div class="field">
                    <label for="email">Alamat Email Terdaftar</label>
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

                <button type="submit" class="btn btn-primary btn-block">Kirim Tautan Reset Kata Sandi</button>
            </form>
        </div>

        <p class="text-center" style="margin-top: 20px;">
            <a href="{{ route('login') }}" class="link-muted">
                <x-icon name="arrow-left" class="icon-sm" /> Kembali ke halaman Masuk
            </a>
        </p>
    </div>
</x-layout>
