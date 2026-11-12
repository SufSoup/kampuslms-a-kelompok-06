<x-layout title="{{ $assignment->title }} — KampusLMS">
    @php
        $backRoute = auth()->user()->role === 'dosen'
            ? route('dosen.assignments.index')
            : route('mahasiswa.assignments.index');
        $isOverdue = $assignment->due_at && now()->gt($assignment->due_at);
    @endphp

    <div class="breadcrumb">
        <a href="{{ $backRoute }}"><x-icon name="arrow-left" class="icon-sm" /> Daftar Tugas</a>
        <x-icon name="chevron-right" class="icon-sm" />
        <span>{{ $assignment->course->code }}</span>
    </div>

    <div class="profile-card">
        <div class="profile-top">
            <div class="profile-chips">
                <span class="badge badge-{{ $assignment->status === 'published' ? 'success' : 'warning' }}">{{ ucfirst($assignment->status) }}</span>
                @if ($isOverdue)
                    <span class="badge badge-danger"><x-icon name="clock" class="icon-sm" /> Melewati tenggat</span>
                @endif
            </div>
            <span class="badge badge-neutral">Nilai maks: {{ $assignment->max_score }}</span>
        </div>

        <h1 class="profile-title">{{ $assignment->title }}</h1>
        <p class="profile-desc">Mata kuliah: {{ $assignment->course->name }} ({{ $assignment->course->code }})</p>

        <div class="detail-grid" style="margin-top: 18px;">
            <div class="detail-item">
                <p class="detail-label">Dibuat Oleh</p>
                <p class="detail-value">{{ $assignment->creator->name }}</p>
            </div>
            <div class="detail-item">
                <p class="detail-label">Batas Pengumpulan</p>
                <p class="detail-value">{{ $assignment->due_at ? $assignment->due_at->format('d M Y H:i') : '-' }}</p>
            </div>
            <div class="detail-item">
                <p class="detail-label">Nilai Maksimum</p>
                <p class="detail-value">{{ $assignment->max_score }}</p>
            </div>
            <div class="detail-item">
                <p class="detail-label">Pengumpulan Terlambat</p>
                <p class="detail-value">{{ $assignment->allow_late ? 'Diizinkan' : 'Tidak diizinkan' }}</p>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="section-header">
            <div>
                <h2 class="section-title">Instruksi</h2>
            </div>
        </div>
        <div class="card">
            <p class="text-secondary" style="white-space: pre-line;">{{ $assignment->instructions }}</p>
        </div>
    </section>

    @if (auth()->user()->role === 'mahasiswa')
        <section class="section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Status Pengumpulan Tugas</h2>
                </div>
            </div>

            @if ($submission)
                <div class="card">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <p class="detail-label">Status</p>
                            <p class="detail-value"><span class="badge badge-success"><x-icon name="check" class="icon-sm" /> Sudah Mengumpulkan</span></p>
                        </div>
                        <div class="detail-item">
                            <p class="detail-label">Berkas</p>
                            <p class="detail-value">{{ $submission->original_name }} ({{ $submission->formatted_file_size }})</p>
                        </div>
                        <div class="detail-item">
                            <p class="detail-label">Waktu Pengumpulan</p>
                            <p class="detail-value">{{ $submission->submitted_at?->format('d M Y H:i') }}</p>
                        </div>
                        <div class="detail-item">
                            <p class="detail-label">Ketepatan Waktu</p>
                            <p class="detail-value">
                                @if ($submission->is_late)
                                    <span class="badge badge-danger">Terlambat</span>
                                @else
                                    <span class="badge badge-success">Tepat Waktu</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    @if ($submission->note)
                        <div class="detail-item" style="margin-top: 16px;">
                            <p class="detail-label">Catatan</p>
                            <p class="detail-value">{{ $submission->note }}</p>
                        </div>
                    @endif

                    @if ($submission->grade)
                        <!-- Hasil penilaian dosen -->
                        <div style="border-top: 1px solid var(--c-border-soft); margin-top: 18px; padding-top: 18px;">
                            <h3 class="section-title" style="font-size: 17px; margin-bottom: 12px;">Hasil Penilaian</h3>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <p class="detail-label">Nilai</p>
                                    <p class="detail-value">
                                        <span class="badge badge-primary">{{ number_format((float) $submission->grade->score, 2) }} / {{ $assignment->max_score }}</span>
                                    </p>
                                </div>
                                @if ($submission->grade->graded_at)
                                    <div class="detail-item">
                                        <p class="detail-label">Tanggal penilaian</p>
                                        <p class="detail-value">{{ $submission->grade->graded_at->format('d M Y H:i') }}</p>
                                    </div>
                                @endif
                                @if ($submission->grade->grader)
                                    <div class="detail-item">
                                        <p class="detail-label">Dinilai Oleh</p>
                                        <p class="detail-value">{{ $submission->grade->grader->name }}</p>
                                    </div>
                                @endif
                            </div>
                            @if ($submission->grade->feedback)
                                <div class="detail-item" style="margin-top: 16px;">
                                    <p class="detail-label">Feedback Dosen</p>
                                    <p class="detail-value text-break">{{ $submission->grade->feedback }}</p>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="detail-item" style="margin-top: 16px;">
                            <p class="detail-label">Nilai</p>
                            <p class="detail-value text-muted">Belum dinilai.</p>
                        </div>
                    @endif

                    <div class="form-actions" style="margin-top: 18px;">
                        <a href="{{ route('submissions.download', $submission) }}" class="btn btn-secondary">
                            <x-icon name="download" class="icon-sm" /> Unduh Berkas Saya
                        </a>
                    </div>
                </div>
            @else
                <div class="alert alert-warning" style="margin-bottom: 16px;">
                    <x-icon name="alert-triangle" class="icon" />
                    <div>Anda belum mengumpulkan tugas ini.</div>
                </div>
            @endif

            @if ($assignment->due_at && now()->gt($assignment->due_at) && ! $assignment->allow_late)
                <div class="alert alert-danger" style="margin-bottom: 0;">
                    <x-icon name="alert-circle" class="icon" />
                    <div>Batas waktu pengumpulan tugas telah berakhir dan pengumpulan terlambat tidak diizinkan.</div>
                </div>
            @else
                <div class="card" style="margin-top: 20px;">
                    <h3 class="section-title" style="margin-bottom: 16px;">
                        {{ $submission ? 'Kumpulkan Ulang Tugas' : 'Form Pengumpulan Tugas' }}
                    </h3>
                    <form method="POST" action="{{ route('mahasiswa.assignments.submissions.store', $assignment) }}" enctype="multipart/form-data" class="form">
                        @csrf
                        <div class="field">
                            <label for="file">Berkas Tugas</label>
                            <input type="file" id="file" name="file" accept=".pdf,.doc,.docx" required class="input {{ $errors->has('file') ? 'is-invalid' : '' }}">
                            <span class="help-text">Format: PDF, DOC, atau DOCX; maksimal 5 MB.</span>
                        </div>
                        <div class="field">
                            <label for="note">Catatan (opsional)</label>
                            <textarea id="note" name="note" rows="3" class="input" placeholder="Tambahkan catatan jika ada...">{{ old('note', $submission?->note) }}</textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <x-icon name="upload" class="icon-sm" /> {{ $submission ? 'Kumpulkan Ulang' : 'Kirim Tugas' }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </section>
    @endif

    @if (auth()->user()->role === 'dosen')
        <section class="section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Aksi Pengampu</h2>
                </div>
            </div>
            <div class="form-actions">
                <a href="{{ route('dosen.assignments.submissions.index', $assignment) }}" class="btn btn-secondary">
                    <x-icon name="users" class="icon-sm" /> Monitoring Pengumpulan
                </a>
                <a href="{{ route('dosen.assignments.edit', $assignment) }}" class="btn btn-secondary">
                    <x-icon name="edit" class="icon-sm" /> Edit Tugas
                </a>
                <form method="POST" action="{{ route('dosen.assignments.destroy', $assignment) }}" onsubmit="return confirm('Hapus tugas ini?');" style="margin: 0;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <x-icon name="trash" class="icon-sm" /> Hapus Tugas
                    </button>
                </form>
            </div>
        </section>
    @endif
</x-layout>
