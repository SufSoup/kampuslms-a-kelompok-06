<x-layout title="Lupa Kata Sandi — KampusLMS">
    <div style="max-width: 480px; margin: 0 auto;">
        <h1 style="font-size: 36px; margin-bottom: 8px;">Lupa Kata Sandi</h1>
        <p style="color: #6E6E73; margin-bottom: 32px;">
            Masukkan alamat email yang terdaftar. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi Anda.
        </p>

        @if (session('success'))
            <div style="background: #DEF7EC; border: 1px solid #BCF0DA; color: #03543F; padding: 12px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 14px;">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div style="background: #FEE2E2; border: 1px solid #FCA5A5; color: #991B1B; padding: 12px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 14px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" style="max-width: 100%;">
            @csrf

            <div>
                <label for="email">Alamat Email Terdaftar</label>
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

            <button type="submit" style="width: 100%; padding: 12px 24px; font-size: 16px; margin-top: 16px;">
                Kirim Tautan Reset Kata Sandi
            </button>

            <div style="text-align: center; margin-top: 24px;">
                <a href="{{ route('login') }}" style="font-size: 14px; color: #0071E3; text-decoration: none;">
                    &larr; Kembali ke halaman Masuk
                </a>
            </div>
        </form>
    </div>
</x-layout>
