<x-layout title="Dashboard — KampusLMS">
    <div class="page-header">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-desc">
            Selamat datang di portal akademik KampusLMS, <strong>{{ auth()->user()->name }}</strong>.
        </p>
    </div>

    @if (auth()->user()->role === 'admin')
        <!-- Dashboard Admin -->
        <div class="stat-grid section">
            <div class="stat-card">
                <span class="stat-icon"><x-icon name="users" class="icon" /></span>
                <div>
                    <p class="stat-label">Pengguna</p>
                    <p class="stat-value">{{ $stats['users'] }}</p>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon"><x-icon name="book" class="icon" /></span>
                <div>
                    <p class="stat-label">Mata Kuliah</p>
                    <p class="stat-value">{{ $stats['courses'] }}</p>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon tone-purple"><x-icon name="file-text" class="icon" /></span>
                <div>
                    <p class="stat-label">Materi</p>
                    <p class="stat-value">{{ $stats['materials'] }}</p>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon tone-success"><x-icon name="clipboard" class="icon" /></span>
                <div>
                    <p class="stat-label">Tugas</p>
                    <p class="stat-value">{{ $stats['assignments'] }}</p>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Aksi Cepat</h2>
                    <p class="section-desc">Pintasan untuk pengelolaan sistem.</p>
                </div>
            </div>
            <div class="quick-links">
                <a href="{{ route('admin.courses.index') }}" class="quick-link">
                    <x-icon name="book" class="icon" /> Kelola Mata Kuliah
                </a>
                <a href="{{ route('admin.users.index') }}" class="quick-link">
                    <x-icon name="users" class="icon" /> Kelola Pengguna
                </a>
            </div>
        </div>

        <div class="card">
            <h3 class="section-title" style="margin-bottom: 10px;">Panduan Ujian Tengah Semester (UTS)</h3>
            <p class="text-secondary" style="font-size: 14px; margin-bottom: 8px;">Alur demo UTS Admin:</p>
            <ol class="guide-list">
                <li>Buka menu <a href="{{ route('admin.courses.index') }}">Mata Kuliah</a> lalu klik <em>Tambah Mata Kuliah</em>.</li>
                <li>Tentukan nama, kode, SKS, dan dosen pengampu mata kuliah.</li>
                <li>Buka mata kuliah yang dibuat untuk mendaftarkan (enroll) mahasiswa.</li>
            </ol>
        </div>

    @elseif (auth()->user()->role === 'dosen')
        <!-- Dashboard Dosen -->
        <div class="section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Mata Kuliah yang Anda Ampu</h2>
                    <p class="section-desc">Total: {{ $taughtCourses->count() }} mata kuliah</p>
                </div>
            </div>

            @if ($taughtCourses->isEmpty())
                <div class="empty-state">
                    <x-icon name="book" class="icon" />
                    <span>Belum ada mata kuliah yang ditugaskan kepada Anda.</span>
                </div>
            @else
                <div class="course-grid">
                    @foreach ($taughtCourses as $course)
                        <div class="course-card">
                            <div class="course-card-top">
                                <span class="chip">{{ $course->code }}</span>
                                <span class="badge badge-neutral">{{ $course->sks }} SKS</span>
                            </div>
                            <h3 class="course-title">
                                <a href="{{ route('dosen.courses.show', $course) }}">{{ $course->name }}</a>
                            </h3>
                            <p class="course-desc">{{ Str::limit($course->description, 80) }}</p>
                            <div class="course-foot">
                                <span class="course-foot-meta">
                                    <x-icon name="users" class="icon-sm" />
                                    {{ $course->students_count }} Mahasiswa
                                    &middot;
                                    <span class="text-warning">Submission belum dinilai: {{ $course->ungraded_submissions_count }}</span>
                                </span>
                                <span class="course-foot-meta">
                                    <a href="{{ route('dosen.courses.show', $course) }}" class="btn-ghost">
                                        Buka Kelas <x-icon name="arrow-right" class="icon-sm" />
                                    </a>
                                    <a href="{{ route('dosen.courses.grades.index', $course) }}" class="btn-ghost">
                                        Rekap nilai
                                    </a>
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    @elseif (auth()->user()->role === 'mahasiswa')
        <!-- Dashboard Mahasiswa -->
        <div class="section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Mata Kuliah yang Anda Ikuti</h2>
                    <p class="section-desc">Total: {{ $enrolledCourses->count() }} mata kuliah</p>
                </div>
            </div>

            @if ($enrolledCourses->isEmpty())
                <div class="empty-state">
                    <x-icon name="book" class="icon" />
                    <span>Anda belum terdaftar di mata kuliah mana pun.</span>
                </div>
            @else
                <div class="course-grid">
                    @foreach ($enrolledCourses as $course)
                        <div class="course-card">
                            <div class="course-card-top">
                                <span class="chip">{{ $course->code }}</span>
                                <span class="badge badge-neutral">{{ $course->sks }} SKS</span>
                            </div>
                            <h3 class="course-title">
                                <a href="{{ route('mahasiswa.courses.show', $course) }}">{{ $course->name }}</a>
                            </h3>
                            <p class="course-desc">Dosen: {{ $course->lecturer?->name ?? 'Belum ditentukan' }}</p>
                            <div class="course-foot">
                                <span class="course-foot-meta">
                                    <x-icon name="file-text" class="icon-sm" /> {{ $course->materials_count }} Materi
                                    &middot;
                                    <x-icon name="clipboard" class="icon-sm" /> {{ $course->assignments_count }} Tugas
                                </span>
                                <a href="{{ route('mahasiswa.courses.show', $course) }}" class="btn-ghost">
                                    Masuk <x-icon name="arrow-right" class="icon-sm" />
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <section class="section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Tugas Aktif</h2>
                    <p class="section-desc">Total: {{ $activeAssignmentsCount }} tugas dengan tenggat mendatang</p>
                </div>
                <a href="{{ route('mahasiswa.grades.index') }}" class="btn btn-secondary btn-sm">
                    <x-icon name="award" class="icon-sm" /> Rekap nilai &amp; feedback saya
                </a>
            </div>

            @if ($activeAssignments->isEmpty())
                <div class="empty-state">
                    <x-icon name="check-circle" class="icon" />
                    <span>Tidak ada tugas aktif dengan tenggat yang akan datang.</span>
                </div>
            @else
                <div class="item-list">
                    @foreach ($activeAssignments as $assignment)
                        <div class="item-card">
                            <div class="item-main">
                                <h3 class="item-title">{{ $assignment->title }}</h3>
                                <div class="item-meta">
                                    <span><x-icon name="book" class="icon-sm" /> {{ $assignment->course->name }}</span>
                                    <span><x-icon name="clock" class="icon-sm" /> Tenggat {{ $assignment->due_at->format('d M Y, H:i') }}</span>
                                </div>
                            </div>
                            <div class="item-actions">
                                <a href="{{ route('mahasiswa.assignments.show', $assignment) }}" class="btn btn-secondary btn-sm">
                                    Lihat Tugas
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</x-layout>
