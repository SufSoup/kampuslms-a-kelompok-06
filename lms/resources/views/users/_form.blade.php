@if ($errors->any())
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<form method="POST" action="{{ $formAction }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <label for="name">Nama</label>
    <input id="name" name="name" value="{{ old('name', $user->name ?? '') }}" required>

    <label for="email">Email</label>
    <input id="email" name="email" type="email" value="{{ old('email', $user->email ?? '') }}" required>

    <label for="password">Password</label>
    <input id="password" name="password" type="password" {{ isset($user) ? '' : 'required' }}>
    @isset($user)
        <small>Kosongkan jika password tidak diubah.</small>
    @endisset

    <label for="nim_nip">NIM/NIP</label>
    <input id="nim_nip" name="nim_nip" value="{{ old('nim_nip', $user->nim_nip ?? '') }}">

    <label for="role">Role</label>
    <select id="role" name="role" required>
        @foreach (['admin', 'dosen', 'mahasiswa'] as $role)
            <option value="{{ $role }}" @selected(old('role', $user->role ?? '') === $role)>
                {{ ucfirst($role) }}
            </option>
        @endforeach
    </select>

    <button type="submit">Simpan</button>
</form>
