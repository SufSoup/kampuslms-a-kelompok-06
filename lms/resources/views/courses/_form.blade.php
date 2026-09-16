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

    <label for="code">Kode</label>
    <input id="code" name="code" value="{{ old('code', $course->code ?? '') }}" required>

    <label for="name">Nama</label>
    <input id="name" name="name" value="{{ old('name', $course->name ?? '') }}" required>

    <label for="description">Deskripsi</label>
    <textarea id="description" name="description" required>{{ old('description', $course->description ?? '') }}</textarea>

    <label for="sks">SKS</label>
    <input id="sks" name="sks" type="number" min="1" max="255" value="{{ old('sks', $course->sks ?? '') }}" required>

    <label for="lecturer_id">Dosen</label>
    <select id="lecturer_id" name="lecturer_id" required>
        <option value="">Pilih dosen</option>
        @foreach ($lecturers as $lecturer)
            <option value="{{ $lecturer->id }}" @selected(old('lecturer_id', $course->lecturer_id ?? '') == $lecturer->id)>
                {{ $lecturer->name }}
            </option>
        @endforeach
    </select>

    <label for="status">Status</label>
    <select id="status" name="status" required>
        @foreach (['draft', 'active', 'archived'] as $status)
            <option value="{{ $status }}" @selected(old('status', $course->status ?? 'draft') === $status)>
                {{ ucfirst($status) }}
            </option>
        @endforeach
    </select>

    <button type="submit">Simpan</button>
</form>
