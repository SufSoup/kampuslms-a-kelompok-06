<x-layout title="{{ $assignment->title }}">
    @if (session('success'))
        <div style="background: #DEF7EC; border: 1px solid #BCF0DA; color: #03543F; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="background: #FEE2E2; border: 1px solid #FCA5A5; color: #991B1B; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <h1>{{ $assignment->title }}</h1>
    <p>Mata kuliah: {{ $assignment->course->name }} ({{ $assignment->course->code }})</p>
    <p>Dibuat oleh: {{ $assignment->creator->name }}</p>
    <p>Status: {{ ucfirst($assignment->status) }}</p>
    <p>Batas pengumpulan: {{ $assignment->due_at ? $assignment->due_at->format('d M Y H:i') : '-' }}</p>
    <p>Nilai maksimum: {{ $assignment->max_score }}</p>
    <p>Pengumpulan terlambat: {{ $assignment->allow_late ? 'Diizinkan' : 'Tidak diizinkan' }}</p>

    <h2>Instruksi</h2>
    <p>{{ $assignment->instructions }}</p>

    @if (auth()->user()->role === 'mahasiswa')
        <hr style="margin: 24px 0; border: 0; border-top: 1px solid #E5E5EA;">
        <h2>Status Pengumpulan Tugas</h2>

        @if ($submission)
            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                <p><strong>Status:</strong> <span style="color: #059669; font-weight: bold;">Sudah Mengumpulkan</span></p>
                <p><strong>Berkas:</strong> {{ $submission->original_name }} ({{ round($submission->file_size / 1024, 1) }} KB)</p>
                <p><strong>Waktu Pengumpulan:</strong> {{ $submission->submitted_at?->format('d M Y H:i') }}</p>
                <p>
                    <strong>Ketepatan Waktu:</strong>
                    @if ($submission->is_late)
                        <span style="color: #DC2626; font-weight: bold;">Terlambat</span>
                    @else
                        <span style="color: #059669; font-weight: bold;">Tepat Waktu</span>
                    @endif
                </p>
                @if ($submission->note)
                    <p><strong>Catatan:</strong> {{ $submission->note }}</p>
                @endif
                <p>
                    <a href="{{ route('submissions.download', $submission) }}" style="display: inline-block; padding: 6px 12px; background: #0284C7; color: #fff; text-decoration: none; border-radius: 6px; font-size: 14px;">
                        Unduh Berkas Saya
                    </a>
                </p>
            </div>
        @else
            <div style="background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                Anda belum mengumpulkan tugas ini.
            </div>
        @endif

        @if ($assignment->due_at && now()->gt($assignment->due_at) && ! $assignment->allow_late)
            <div style="background: #FEE2E2; border: 1px solid #FCA5A5; color: #991B1B; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                Batas waktu pengumpulan tugas telah berakhir dan pengumpulan terlambat tidak diizinkan.
            </div>
        @else
            <h3>{{ $submission ? 'Kumpulkan Ulang Tugas' : 'Form Pengumpulan Tugas' }}</h3>
            <form method="POST" action="{{ route('mahasiswa.assignments.submissions.store', $assignment) }}" enctype="multipart/form-data" style="max-width: 500px;">
                @csrf
                <div style="margin-bottom: 16px;">
                    <label for="file" style="display: block; font-weight: bold; margin-bottom: 4px;">Berkas Tugas (PDF, DOC, DOCX, maks 5 MB):</label>
                    <input type="file" id="file" name="file" accept=".pdf,.doc,.docx" required style="display: block; width: 100%;">
                </div>
                <div style="margin-bottom: 16px;">
                    <label for="note" style="display: block; font-weight: bold; margin-bottom: 4px;">Catatan (opsional):</label>
                    <textarea id="note" name="note" rows="3" style="width: 100%; border: 1px solid #CBD5E1; border-radius: 6px; padding: 8px;" placeholder="Tambahkan catatan jika ada...">{{ old('note', $submission?->note) }}</textarea>
                </div>
                <button type="submit" style="padding: 10px 20px; background: #0071E3; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
                    {{ $submission ? 'Kumpulkan Ulang' : 'Kirim Tugas' }}
                </button>
            </form>
        @endif
        <hr style="margin: 24px 0; border: 0; border-top: 1px solid #E5E5EA;">
    @endif

    @if (auth()->user()->role === 'dosen')
        <p><a href="{{ route('dosen.assignments.submissions.index', $assignment) }}">Monitoring Pengumpulan</a></p>
        <a href="{{ route('dosen.assignments.edit', $assignment) }}">Edit Tugas</a>
        <form method="POST" action="{{ route('dosen.assignments.destroy', $assignment) }}" onsubmit="return confirm('Hapus tugas ini?');">
            @csrf
            @method('DELETE')
            <button type="submit">Hapus Tugas</button>
        </form>
        <a href="{{ route('dosen.assignments.index') }}">Kembali ke daftar tugas</a>
    @else
        <a href="{{ route('mahasiswa.assignments.index') }}">Kembali ke daftar tugas</a>
    @endif
</x-layout>
