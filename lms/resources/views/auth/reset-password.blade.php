<x-layout title="Atur Ulang Kata Sandi — KampusLMS">
    <div style="max-width: 480px; margin: 0 auto;">
        <h1 style="font-size: 36px; margin-bottom: 8px;">Kata Sandi Baru</h1>
        <p style="color: #6E6E73; margin-bottom: 32px;">
            Tentukan kata sandi baru untuk akun KampusLMS Anda.
        </p>

        @if ($errors->any())
            <div style="background: #FEE2E2; border: 1px solid #FCA5A5; color: #991B1B; padding: 12px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 14px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" style="max-width: 100%;">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email">Alamat Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email', $email) }}"
                    required
                    readonly
                >
            </div>

            <div>
                <label for="password">Kata Sandi Baru (Minimal 8 karakter)</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autofocus
                    placeholder="••••••••"
                >
            </div>

            <div>
                <label for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    placeholder="••••••••"
                >
            </div>

            <button type="submit" style="width: 100%; padding: 12px 24px; font-size: 16px; margin-top: 16px;">
                Simpan Kata Sandi Baru
            </button>
        </form>
    </div>
</x-layout>
