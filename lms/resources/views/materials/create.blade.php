<x-layout title="Tambah Materi — {{ $course->name }}">
    @php
        $routePrefix = auth()->user()->role === 'admin' ? 'admin' : 'dosen';
        $courseRoute = $routePrefix . '.courses.show';
    @endphp

    <div class="container-medium" style="margin: 0 auto;">
        <div class="breadcrumb">
            <a href="{{ route($courseRoute, $course) }}"><x-icon name="arrow-left" class="icon-sm" /> {{ $course->name }}</a>
        </div>

        <div class="page-header">
            <h1 class="page-title">Tambah Materi</h1>
            <p class="page-desc">Tambahkan materi baru untuk mata kuliah ini.</p>
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
            <form method="POST" enctype="multipart/form-data" action="{{ route($routePrefix . '.courses.materials.store', $course) }}" class="form">
                @csrf

                <div class="field">
                    <label for="title">Judul Materi</label>
                    <input type="text" id="title" name="title" class="input {{ $errors->has('title') ? 'is-invalid' : '' }}" value="{{ old('title') }}" required>
                </div>

                <div class="field">
                    <label for="type">Tipe Materi</label>
                    <select id="type" name="type" class="input {{ $errors->has('type') ? 'is-invalid' : '' }}" required>
                        <option value="">Pilih tipe</option>
                        <option value="file" @selected(old('type') === 'file')>Berkas</option>
                        <option value="link" @selected(old('type') === 'link')>Tautan</option>
                    </select>
                </div>

                <div class="field">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description" rows="4" class="input" placeholder="Deskripsi singkat mengenai materi ini...">{{ old('description') }}</textarea>
                </div>

                <div class="field" data-material-link>
                    <label for="external_url">URL (untuk tautan)</label>
                    <input type="url" id="external_url" name="external_url" class="input {{ $errors->has('external_url') ? 'is-invalid' : '' }}" value="{{ old('external_url') }}" placeholder="https://example.com">
                </div>

                <div class="field" data-material-file>
                    <label for="file">Unggah berkas materi</label>
                    <input type="file" id="file" name="file" class="input {{ $errors->has('file') ? 'is-invalid' : '' }}" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.zip">
                    <span class="help-text">Format: PDF, Word, PowerPoint, Excel, TXT, atau ZIP; maksimal 20 MB.</span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check" class="icon-sm" /> Simpan Materi
                    </button>
                    <a href="{{ route($courseRoute, $course) }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        const typeSelect = document.getElementById('type');
        const fileField = document.querySelector('[data-material-file]');
        const linkField = document.querySelector('[data-material-link]');
        const fileInput = document.getElementById('file');
        const urlInput = document.getElementById('external_url');

        function updateMaterialFields() {
            const isFile = typeSelect.value === 'file';
            fileField.hidden = !isFile;
            linkField.hidden = !typeSelect.value || isFile;
            fileInput.required = isFile;
            urlInput.required = typeSelect.value === 'link';
        }

        typeSelect.addEventListener('change', updateMaterialFields);
        updateMaterialFields();
    </script>
</x-layout>
