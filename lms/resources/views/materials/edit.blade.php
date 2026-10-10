<x-layout title="Edit Materi — {{ $material->title }}">
    <div style="max-width: 640px; margin: 0 auto;">
        <div style="margin-bottom: 24px;">
            <a href="{{ auth()->user()->role === 'admin' ? route('admin.courses.show', $course) : route('dosen.courses.show', $course) }}" style="font-size: 14px; color: #6E6E73;">
                ← Kembali ke {{ $course->name }}
            </a>
            <h1 style="font-size: 32px; margin-top: 12px; margin-bottom: 4px;">Edit Materi</h1>
            <p style="color: #6E6E73; margin: 0;">Perbarui detail materi yang dipilih.</p>
        </div>

        @if ($errors->any())
            <div style="background: #FEE2E2; border: 1px solid #FCA5A5; color: #991B1B; padding: 12px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 14px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('dosen.materials.update', $material) }}" style="display: flex; flex-direction: column; gap: 18px;">
            @csrf
            @method('PUT')

            <div>
                <label for="title">Judul Materi</label>
                <input type="text" id="title" name="title" value="{{ old('title', $material->title) }}" required>
            </div>

            <div>
                <label for="type">Tipe Materi</label>
                <select id="type" name="type" required>
                    <option value="file" @selected(old('type', $material->type) === 'file')>Berkas</option>
                    <option value="link" @selected(old('type', $material->type) === 'link')>Tautan</option>
                </select>
            </div>

            <div>
                <label for="description">Deskripsi</label>
                <textarea id="description" name="description" rows="4">{{ old('description', $material->description) }}</textarea>
            </div>

            <div>
                <label for="external_url">URL (untuk tautan)</label>
                <input type="url" id="external_url" name="external_url" value="{{ old('external_url', $material->external_url) }}" placeholder="https://example.com">
            </div>

            <div>
                <label for="file_path">Lokasi berkas (opsional)</label>
                <input type="text" id="file_path" name="file_path" value="{{ old('file_path', $material->file_path) }}" placeholder="storage/uploads/materi.pdf">
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="submit">Perbarui Materi</button>
                <a href="{{ auth()->user()->role === 'admin' ? route('admin.courses.show', $course) : route('dosen.courses.show', $course) }}" style="display: inline-flex; align-items: center; padding: 10px 20px; border-radius: 980px; font-size: 14px; color: #1D1D1F; border: 1px solid #E5E5EA; text-decoration: none;">
                    Batal
                </a>
            </div>
        </form>
    </div>
</x-layout>
