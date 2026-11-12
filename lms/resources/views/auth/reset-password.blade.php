<x-layout title="Atur Ulang Kata Sandi — KampusLMS">
    <div class="auth-box">
        <div class="text-center" style="margin-bottom: 24px;">
            <h1 class="page-title">Kata Sandi Baru</h1>
            <p class="page-desc">Tentukan kata sandi baru untuk akun KampusLMS Anda.</p>
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

            <form method="POST" action="{{ route('password.update') }}" class="form">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="field">
                    <label for="email">Alamat Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="input"
                        value="{{ old('email', $email) }}"
                        required
                        readonly
                    >
                </div>

                <div class="field">
                    <label for="password">Kata Sandi Baru</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                        required
                        autofocus
                        placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                    >
                    <span class="help-text">Minimal 8 karakter.</span>
                </div>

                <div class="field">
                    <label for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        class="input {{ $errors->has('password_confirmation') ? 'is-invalid' : '' }}"
                        required
                        placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">Simpan Kata Sandi Baru</button>
            </form>
        </div>
    </div>
</x-layout>
