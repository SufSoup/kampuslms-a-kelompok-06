<x-layout title="Detail Pengumpulan — KampusLMS">
    @php
        $backRoute = auth()->user()->role === 'mahasiswa'
            ? route('mahasiswa.assignments.show', $submission->assignment)
            : route('dosen.assignments.show', $submission->assignment);
    @endphp

    <div class="breadcrumb">
        <a href="{{ $backRoute }}"><x-icon name="arrow-left" class="icon-sm" /> Kembali ke tugas</a>
    </div>

    <div class="page-header">
        <h1 class="page-title">Detail Pengumpulan</h1>
        <p class="page-desc">{{ $submission->assignment->title }} &mdash; {{ $submission->assignment->course->name }}</p>
    </div>

    <div class="profile-card">
        <div class="profile-top">
            <span class="badge {{ $submission->is_late ? 'badge-danger' : 'badge-success' }}">
                {{ $submission->is_late ? 'Terlambat' : 'Tepat waktu' }}
            </span>
        </div>

        <div class="detail-grid">
            <div class="detail-item">
                <p class="detail-label">Mahasiswa</p>
                <p class="detail-value">{{ $submission->student->name }}</p>
            </div>
            <div class="detail-item">
                <p class="detail-label">Nama Berkas</p>
                <p class="detail-value">{{ $submission->original_name }}</p>
            </div>
            <div class="detail-item">
                <p class="detail-label">Ukuran</p>
                <p class="detail-value">{{ $submission->formatted_file_size }}</p>
            </div>
            <div class="detail-item">
                <p class="detail-label">Dikumpulkan</p>
                <p class="detail-value">{{ $submission->submitted_at->format('d M Y H:i') }}</p>
            </div>
        </div>
    </div>

    @if ($submission->note)
        <section class="section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Catatan Mahasiswa</h2>
                </div>
            </div>
            <div class="card">
                <p class="text-secondary" style="white-space: pre-line;">{{ $submission->note }}</p>
            </div>
        </section>
    @endif

    @if ($submission->grade)
        <section class="section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Nilai</h2>
                </div>
            </div>
            <div class="card">
                <div class="detail-grid">
                    <div class="detail-item">
                        <p class="detail-label">Skor</p>
                        <p class="detail-value"><span class="badge badge-primary">{{ $submission->grade->score }}</span></p>
                    </div>
                </div>
                @if ($submission->grade->feedback)
                    <div class="detail-item" style="margin-top: 16px;">
                        <p class="detail-label">Feedback</p>
                        <p class="detail-value text-break">{{ $submission->grade->feedback }}</p>
                    </div>
                @endif
            </div>
        </section>
    @endif
</x-layout>
