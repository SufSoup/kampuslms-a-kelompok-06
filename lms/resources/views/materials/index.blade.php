<x-layout title="Materi — {{ $course->name }}">
    @php
        $isAdmin = auth()->user()->role === 'admin';
        $backRoute = $isAdmin ? route('admin.courses.show', $course) : route('dosen.courses.show', $course);
        $createRoute = $isAdmin ? route('admin.courses.materials.create', $course) : route('dosen.courses.materials.create', $course);
        $showRoute = fn($material) => $isAdmin ? route('admin.materials.show', $material) : route('dosen.materials.show', $material);
        $editRoute = fn($material) => $isAdmin ? route('admin.materials.edit', $material) : route('dosen.materials.edit', $material);
        $destroyRoute = fn($material) => $isAdmin ? route('admin.materials.destroy', $material) : route('dosen.materials.destroy', $material);
    @endphp

    <div style="max-width: 900px; margin: 0 auto;">
        <div style="margin-bottom: 24px;">
            <a href="{{ $backRoute }}" style="font-size: 14px; color: #6E6E73;">
                ← Kembali ke {{ $course->name }}
            </a>
            <h1 style="font-size: 32px; margin-top: 12px; margin-bottom: 4px;">Daftar Materi</h1>
            <p style="color: #6E6E73; margin-bottom: 0;">Semua materi yang tersedia pada mata kuliah ini.</p>
        </div>

        @if (auth()->user()->role === 'admin' || (auth()->user()->role === 'dosen' && $course->lecturer_id === auth()->id()))
            <div style="margin-bottom: 20px;">
                <a href="{{ $createRoute }}" style="display: inline-block; background: #000; color: #fff; padding: 10px 18px; border-radius: 999px; text-decoration: none;">+ Tambah Materi</a>
            </div>
        @endif

        @if ($materials->isEmpty())
            <div style="padding: 32px; background: #F5F5F7; border: 1px dashed #D1D1D6; border-radius: 12px; text-align: center; color: #6E6E73;">
                Belum ada materi yang ditambahkan pada mata kuliah ini.
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 14px;">
                @foreach ($materials as $material)
                    <div style="background: #FFFFFF; border: 1px solid #E5E5EA; border-radius: 12px; padding: 20px; display: flex; justify-content: space-between; align-items: center; gap: 16px;">
                        <div>
                            <div style="font-size: 12px; font-weight: 600; text-transform: uppercase; color: #8E8E93; margin-bottom: 6px;">
                                {{ $material->type === 'file' ? 'Berkas' : 'Tautan' }}
                            </div>
                            <h3 style="font-size: 20px; margin: 0 0 6px;">{{ $material->title }}</h3>
                            @if ($material->description)
                                <p style="margin: 0; color: #6E6E73;">{{ $material->description }}</p>
                            @endif
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <a href="{{ $showRoute($material) }}" style="color: #0071E3; font-weight: 600; text-decoration: none;">Lihat</a>
                            @if (auth()->user()->role === 'admin' || (auth()->user()->role === 'dosen' && $course->lecturer_id === auth()->id()))
                                <a href="{{ $editRoute($material) }}" style="color: #1D1D1F; border: 1px solid #D1D1D6; padding: 6px 12px; border-radius: 999px; text-decoration: none;">Edit</a>
                                <form method="POST" action="{{ $destroyRoute($material) }}" onsubmit="return confirm('Hapus materi ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: transparent; color: #DC2626; border: 1px solid #FCA5A5; padding: 6px 12px; border-radius: 999px; cursor: pointer;">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layout>
