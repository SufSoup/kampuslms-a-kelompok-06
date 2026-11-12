<x-layout title="{{ $material->title }} — KampusLMS">
    @php
        $routePrefix = match (auth()->user()->role) {
            'admin' => 'admin',
            'dosen' => 'dosen',
            'mahasiswa' => 'mahasiswa',
        };
    @endphp

    <div class="container-medium" style="margin: 0 auto;">
        <div class="breadcrumb">
            <a href="{{ route($routePrefix . '.courses.materials.index', $material->course) }}">
                <x-icon name="arrow-left" class="icon-sm" /> Daftar materi {{ $material->course->name }}
            </a>
        </div>

        <div class="profile-card">
            <div class="profile-top">
                <span class="badge badge-neutral">{{ $material->type === 'file' ? 'Berkas' : 'Tautan' }}</span>
            </div>
            <h1 class="profile-title">{{ $material->title }}</h1>
            @if ($material->description)
                <p class="profile-desc">{{ $material->description }}</p>
            @endif

            <div class="detail-grid" style="margin-top: 18px;">
                <div class="detail-item">
                    <p class="detail-label">Mata Kuliah</p>
                    <p class="detail-value">{{ $material->course->name }}</p>
                </div>
                <div class="detail-item">
                    <p class="detail-label">Jenis</p>
                    <p class="detail-value">{{ $material->type === 'file' ? 'Berkas' : 'Tautan' }}</p>
                </div>
            </div>
        </div>

        @if ($material->type === 'link' && $material->external_url)
            <div class="card section">
                <h2 class="section-title" style="margin-bottom: 8px;">URL Referensi</h2>
                <a href="{{ $material->external_url }}" target="_blank" rel="noopener noreferrer" style="overflow-wrap: anywhere;">
                    {{ $material->external_url }} <x-icon name="external" class="icon-sm" />
                </a>
            </div>
        @elseif ($material->type === 'file' && $material->file_path)
            <div class="card section">
                <h2 class="section-title" style="margin-bottom: 8px;">Berkas Materi</h2>
                <a href="{{ route($routePrefix . '.materials.download', $material) }}" class="btn btn-primary">
                    <x-icon name="download" class="icon-sm" /> Unduh {{ $material->original_name ?? 'berkas materi' }}
                </a>
            </div>
        @endif
    </div>
</x-layout>
