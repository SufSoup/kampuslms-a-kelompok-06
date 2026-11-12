<form method="POST" action="{{ $formAction }}" class="form">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="field">
        <label for="code">Kode</label>
        <input id="code" name="code" class="input {{ $errors->has('code') ? 'is-invalid' : '' }}" value="{{ old('code', $course->code ?? '') }}" required>
        @error('code')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field">
        <label for="name">Nama</label>
        <input id="name" name="name" class="input {{ $errors->has('name') ? 'is-invalid' : '' }}" value="{{ old('name', $course->name ?? '') }}" required>
        @error('name')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field">
        <label for="description">Deskripsi</label>
        <textarea id="description" name="description" class="input {{ $errors->has('description') ? 'is-invalid' : '' }}" required>{{ old('description', $course->description ?? '') }}</textarea>
        @error('description')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field">
        <label for="sks">SKS</label>
        <input id="sks" name="sks" type="number" min="1" max="255" class="input {{ $errors->has('sks') ? 'is-invalid' : '' }}" value="{{ old('sks', $course->sks ?? '') }}" required>
        @error('sks')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field">
        <label for="lecturer_id">Dosen</label>
        <select id="lecturer_id" name="lecturer_id" class="input {{ $errors->has('lecturer_id') ? 'is-invalid' : '' }}" required>
            <option value="">Pilih dosen</option>
            @foreach ($lecturers as $lecturer)
                <option value="{{ $lecturer->id }}" @selected(old('lecturer_id', $course->lecturer_id ?? '') == $lecturer->id)>
                    {{ $lecturer->name }}
                </option>
            @endforeach
        </select>
        @error('lecturer_id')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status" class="input {{ $errors->has('status') ? 'is-invalid' : '' }}" required>
            @foreach (['draft', 'active', 'archived'] as $status)
                <option value="{{ $status }}" @selected(old('status', $course->status ?? 'draft') === $status)>
                    {{ ucfirst($status) }}
                </option>
            @endforeach
        </select>
        @error('status')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            <x-icon name="check" class="icon-sm" /> Simpan
        </button>
    </div>
</form>
