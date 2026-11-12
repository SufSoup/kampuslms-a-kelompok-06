<form method="POST" action="{{ $formAction }}" class="form">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="field">
        <label for="name">Nama</label>
        <input
            id="name"
            name="name"
            class="input {{ $errors->has('name') ? 'is-invalid' : '' }}"
            value="{{ old('name', $user->name ?? '') }}"
            required
        >
        @error('name')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field">
        <label for="email">Email</label>
        <input
            id="email"
            name="email"
            type="email"
            class="input {{ $errors->has('email') ? 'is-invalid' : '' }}"
            value="{{ old('email', $user->email ?? '') }}"
            required
        >
        @error('email')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field">
        <label for="password">Password</label>
        <input
            id="password"
            name="password"
            type="password"
            class="input {{ $errors->has('password') ? 'is-invalid' : '' }}"
            {{ isset($user) ? '' : 'required' }}
        >
        @error('password')
            <p class="field-error">{{ $message }}</p>
        @enderror
        @isset($user)
            <span class="help-text">Kosongkan jika password tidak diubah.</span>
        @endisset
    </div>

    <div class="field">
        <label for="nim_nip">NIM/NIP</label>
        <input
            id="nim_nip"
            name="nim_nip"
            class="input {{ $errors->has('nim_nip') ? 'is-invalid' : '' }}"
            value="{{ old('nim_nip', $user->nim_nip ?? '') }}"
        >
        @error('nim_nip')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field">
        <label for="role">Role</label>
        <select id="role" name="role" class="input {{ $errors->has('role') ? 'is-invalid' : '' }}" required>
            @foreach (['admin', 'dosen', 'mahasiswa'] as $role)
                <option
                    value="{{ $role }}"
                    @selected(old('role', $user->role ?? '') === $role)
                >
                    {{ ucfirst($role) }}
                </option>
            @endforeach
        </select>
        @error('role')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            <x-icon name="check" class="icon-sm" /> Simpan
        </button>
    </div>
</form>
