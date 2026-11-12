<x-layout title="Edit Sumber Belajar — {{ $resource->title }}">
    @php
        $courseShowRoute = auth()->user()->role === 'admin'
            ? route('admin.courses.show', $course)
            : route('dosen.courses.show', $course);
    @endphp

    <div class="container-medium" style="margin: 0 auto;">
        <div class="breadcrumb">
            <a href="{{ $courseShowRoute }}"><x-icon name="arrow-left" class="icon-sm" /> {{ $course->name }}</a>
        </div>

        <div class="page-header">
            <h1 class="page-title">Edit Sumber Belajar</h1>
            <p class="page-desc">Perbarui informasi kurasi sumber belajar.</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <x-icon name="alert-circle" class="icon" />
                <div>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="card">
            <form method="POST" action="{{ route('learning-resources.update', [$course, $resource]) }}" class="form">
                @csrf
                @method('PUT')

                <div class="field">
                    <label for="title">Judul Referensi</label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        class="input {{ $errors->has('title') ? 'is-invalid' : '' }}"
                        value="{{ old('title', $resource->title) }}"
                        required
                    >
                </div>

                <div class="field">
                    <label for="type">Tipe Sumber</label>
                    <select id="type" name="type" class="input {{ $errors->has('type') ? 'is-invalid' : '' }}" required>
                        @foreach (['article' => 'Artikel', 'video' => 'Video', 'book' => 'Buku', 'documentation' => 'Dokumentasi', 'other' => 'Lainnya'] as $key => $label)
                            <option value="{{ $key }}" @selected(old('type', $resource->type) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="url">Tautan (URL)</label>
                    <input
                        type="url"
                        id="url"
                        name="url"
                        class="input {{ $errors->has('url') ? 'is-invalid' : '' }}"
                        value="{{ old('url', $resource->url) }}"
                        required
                    >
                </div>

                <div class="field">
                    <label for="source_platform">Platform Sumber (Opsional)</label>
                    <input
                        type="text"
                        id="source_platform"
                        name="source_platform"
                        class="input"
                        value="{{ old('source_platform', $resource->source_platform) }}"
                    >
                </div>

                <div class="field">
                    <label for="description">Deskripsi / Catatan Tambahan (Opsional)</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        class="input"
                    >{{ old('description', $resource->description) }}</textarea>
                </div>

                <label class="checkbox-row">
                    <input type="checkbox" name="is_recommended" value="1" @checked(old('is_recommended', $resource->is_recommended))>
                    Tandai sebagai <strong>Sangat Direkomendasikan</strong>
                </label>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" class="icon-sm" /> Perbarui
                    </button>
                    <a href="{{ $courseShowRoute }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-layout>
