<x-layout title="Materi — {{ $course->name }}">
    @php
        $routePrefix = match (auth()->user()->role) {
            'admin' => 'admin',
            'dosen' => 'dosen',
            'mahasiswa' => 'mahasiswa',
        };
        $backRoute = route($routePrefix . '.courses.show', $course);
        $createRoute = auth()->user()->role === 'mahasiswa'
            ? null
            : route($routePrefix . '.courses.materials.create', $course);
        $showRoute = fn($material) => route($routePrefix . '.materials.show', $material);
        $downloadRoute = fn($material) => route($routePrefix . '.materials.download', $material);
        $editRoute = fn($material) => route($routePrefix . '.materials.edit', $material);
        $destroyRoute = fn($material) => route($routePrefix . '.materials.destroy', $material);
    @endphp

    <div class="container-medium" style="margin: 0 auto;">
        <div class="breadcrumb">
            <a href="{{ $backRoute }}"><x-icon name="arrow-left" class="icon-sm" /> {{ $course->name }}</a>
        </div>

        <div class="page-header page-header-row">
            <div>
                <h1 class="page-title">Daftar Materi</h1>
                <p class="page-desc">Semua materi yang tersedia pada mata kuliah ini.</p>
            </div>
            @can('create', [\App\Models\Material::class, $course])
                <a href="{{ $createRoute }}" class="btn btn-primary">
                    <x-icon name="plus" class="icon-sm" /> Tambah Materi
                </a>
            @endcan
        </div>

        @if ($materials->isEmpty())
            <div class="empty-state">
                <x-icon name="file-text" class="icon" />
                <span>Belum ada materi yang ditambahkan pada mata kuliah ini.</span>
            </div>
        @else
            <div class="item-list">
                @foreach ($materials as $material)
                    <div class="item-card">
                        <div class="item-main">
                            <div class="item-eyebrow">
                                <span class="badge badge-neutral">{{ $material->type === 'file' ? 'Berkas' : 'Tautan' }}</span>
                            </div>
                            <h3 class="item-title">
                                <a href="{{ $showRoute($material) }}">{{ $material->title }}</a>
                            </h3>
                            @if ($material->type === 'file' && $material->original_name)
                                <div class="item-meta"><span><x-icon name="file-text" class="icon-sm" /> {{ $material->original_name }}</span></div>
                            @endif
                            @if ($material->description)
                                <p class="item-desc">{{ $material->description }}</p>
                            @endif
                        </div>
                        <div class="item-actions">
                            <a href="{{ $showRoute($material) }}" class="btn btn-secondary btn-sm">Lihat</a>
                            @if ($material->type === 'file' && $material->file_path)
                                <a href="{{ $downloadRoute($material) }}" class="btn btn-secondary btn-sm">
                                    <x-icon name="download" class="icon-sm" /> Unduh
                                </a>
                            @endif
                            @can('update', $material)
                                <a href="{{ $editRoute($material) }}" class="btn btn-secondary btn-sm">
                                    <x-icon name="edit" class="icon-sm" /> Edit
                                </a>
                            @endcan
                            @can('delete', $material)
                                <form method="POST" action="{{ $destroyRoute($material) }}" onsubmit="return confirm('Hapus materi ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <x-icon name="trash" class="icon-sm" /> Hapus
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="pagination-wrapper">
                {{ $materials->links() }}
            </div>
        @endif
    </div>
</x-layout>
