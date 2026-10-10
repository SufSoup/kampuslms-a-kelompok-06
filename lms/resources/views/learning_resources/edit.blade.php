<x-layout title="Edit Sumber Belajar — {{ $resource->title }}">
    <div style="max-width: 600px; margin: 0 auto;">
        <div style="margin-bottom: 24px;">
            <a href="{{ auth()->user()->role === 'admin' ? route('admin.courses.show', $course) : route('dosen.courses.show', $course) }}" style="font-size: 14px; color: #6E6E73;">
                ← Kembali ke {{ $course->name }}
            </a>
            <h1 style="font-size: 32px; margin-top: 12px; margin-bottom: 4px;">Edit Sumber Belajar</h1>
            <p style="color: #6E6E73;">Perbarui informasi kurasi sumber belajar.</p>
        </div>

        @if ($errors->any())
            <div style="background: #FEE2E2; border: 1px solid #FCA5A5; color: #991B1B; padding: 12px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 14px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('learning-resources.update', [$course, $resource]) }}" style="max-width: 100%;">
            @csrf
            @method('PUT')

            <div>
                <label for="title">Judul Referensi</label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="{{ old('title', $resource->title) }}"
                    required
                >
            </div>

            <div>
                <label for="type">Tipe Sumber</label>
                <select id="type" name="type" required>
                    @foreach (['article' => 'Artikel', 'video' => 'Video', 'book' => 'Buku', 'documentation' => 'Dokumentasi', 'other' => 'Lainnya'] as $key => $label)
                        <option value="{{ $key }}" @selected(old('type', $resource->type) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="url">Tautan (URL)</label>
                <input
                    type="url"
                    id="url"
                    name="url"
                    value="{{ old('url', $resource->url) }}"
                    required
                >
            </div>

            <div>
                <label for="source_platform">Platform Sumber (Opsional)</label>
                <input
                    type="text"
                    id="source_platform"
                    name="source_platform"
                    value="{{ old('source_platform', $resource->source_platform) }}"
                >
            </div>

            <div>
                <label for="description">Deskripsi / Catatan Tambahan (Opsional)</label>
                <textarea
                    id="description"
                    name="description"
                    rows="4"
                >{{ old('description', $resource->description) }}</textarea>
            </div>

            <div style="margin-top: 20px; margin-bottom: 28px;">
                <label style="display: inline-flex; align-items: center; gap: 8px; margin: 0; text-transform: none; font-size: 14px; font-weight: normal; cursor: pointer;">
                    <input type="checkbox" name="is_recommended" value="1" @checked(old('is_recommended', $resource->is_recommended)) style="width: auto; margin: 0;">
                    ⭐ Tandai sebagai <strong>Sangat Direkomendasikan</strong>
                </label>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="submit" style="padding: 10px 24px;">Perbarui</button>
                <a href="{{ auth()->user()->role === 'admin' ? route('admin.courses.show', $course) : route('dosen.courses.show', $course) }}" style="display: inline-flex; align-items: center; padding: 10px 20px; border-radius: 980px; font-size: 14px; color: #1D1D1F; border: 1px solid #E5E5EA; text-decoration: none;">
                    Batal
                </a>
            </div>
        </form>
    </div>
</x-layout>
