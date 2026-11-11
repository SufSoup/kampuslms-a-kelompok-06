<x-layout title="Dashboard — KampusLMS">
    <div style="margin-bottom: 32px;">
        <h1 style="margin-bottom: 8px;">Dashboard</h1>
        <p style="color: #6E6E73;">
            Selamat datang di portal akademik KampusLMS, <strong>{{ auth()->user()->name }}</strong>.
        </p>
    </div>

    @if (auth()->user()->role === 'admin')
        <!-- Dashboard Admin -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 32px;">
            @foreach ([
                'Pengguna' => $stats['users'],
                'Mata Kuliah' => $stats['courses'],
                'Materi' => $stats['materials'],
                'Tugas' => $stats['assignments'],
            ] as $label => $count)
                <div style="background: #F5F5F7; padding: 20px; border-radius: 16px; border: 1px solid #E5E5EA;">
                    <p style="font-size: 13px; font-weight: 600; text-transform: uppercase; color: #6E6E73; margin-bottom: 8px;">{{ $label }}</p>
                    <h2 style="font-size: 28px; font-weight: 700; margin: 0; color: #0071E3;">{{ $count }}</h2>
                </div>
            @endforeach
        </div>

        <div style="background: #F5F5F7; padding: 24px; border-radius: 16px; border: 1px solid #E5E5EA; margin-bottom: 40px;">
            <h2 style="font-size: 20px; margin: 0 0 12px;">Aksi Cepat</h2>
            <div style="display: flex; flex-wrap: wrap; gap: 16px;">
                <a href="{{ route('admin.courses.index') }}" style="color: #0071E3; font-weight: 500;">→ Kelola Mata Kuliah</a>
                <a href="{{ route('admin.users.index') }}" style="color: #0071E3; font-weight: 500;">→ Kelola Pengguna</a>
            </div>
        </div>

        <div>
            <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 16px;">Panduan Ujian Tengah Semester (UTS)</h3>
            <div style="background: #FFFFFF; border: 1px solid #E5E5EA; border-radius: 16px; padding: 20px;">
                <p style="margin-bottom: 8px;"><strong>Alur Demo UTS Admin:</strong></p>
                <ol style="padding-left: 20px; color: #3A3A3C; font-size: 15px; line-height: 1.8;">
                    <li>Buka menu <a href="{{ route('admin.courses.index') }}">Mata Kuliah</a> lalu klik <em>Tambah Mata Kuliah</em>.</li>
                    <li>Tentukan nama, kode, SKS, dan dosen pengampu mata kuliah.</li>
                    <li>Buka mata kuliah yang dibuat untuk mendaftarkan (enroll) mahasiswa.</li>
                </ol>
            </div>
        </div>

    @elseif (auth()->user()->role === 'dosen')
        <!-- Dashboard Dosen -->
        <div style="margin-bottom: 32px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="font-size: 22px; font-weight: 600; margin: 0;">Mata Kuliah yang Anda Ampu</h3>
                <span style="font-size: 14px; color: #6E6E73;">Total: {{ $taughtCourses->count() }} mata kuliah</span>
            </div>

            @if ($taughtCourses->isEmpty())
                <div style="padding: 32px; background: #F5F5F7; border-radius: 16px; text-align: center;">
                    <p style="color: #6E6E73; margin: 0;">Belum ada mata kuliah yang ditugaskan kepada Anda.</p>
                </div>
            @else
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                    @foreach ($taughtCourses as $course)
                        <div style="background: #FFFFFF; border: 1px solid #E5E5EA; border-radius: 16px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                    <span style="font-size: 12px; font-weight: 700; color: #0071E3; background: #EBF5FF; padding: 4px 8px; border-radius: 6px;">{{ $course->code }}</span>
                                    <span style="font-size: 12px; color: #8E8E93;">{{ $course->sks }} SKS</span>
                                </div>
                                <h4 style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">
                                    <a href="{{ route('dosen.courses.show', $course) }}" style="color: #1D1D1F; border-bottom: none;">{{ $course->name }}</a>
                                </h4>
                                <p style="font-size: 13px; color: #6E6E73; margin-bottom: 16px;">{{ Str::limit($course->description, 80) }}</p>
                            </div>
                            <div style="border-top: 1px solid #F5F5F7; padding-top: 14px; font-size: 13px; color: #8E8E93; display: flex; justify-content: space-between; align-items: center;">
                                <span style="display: inline-flex; align-items: center; gap: 4px;">
                                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    {{ $course->students_count }} Mahasiswa · Submission belum dinilai: {{ $course->ungraded_submissions_count }}
                                </span>
                                <span>
                                    <a href="{{ route('dosen.courses.show', $course) }}" style="color: #0071E3; font-weight: 600; border-bottom: none;">Buka Kelas →</a>
                                    <a href="{{ route('dosen.courses.grades.index', $course) }}" style="color: #0071E3; font-weight: 600; border-bottom: none; margin-left: 8px;">Rekap nilai</a>
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    @elseif (auth()->user()->role === 'mahasiswa')
        <!-- Dashboard Mahasiswa -->
        <div style="margin-bottom: 32px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="font-size: 22px; font-weight: 600; margin: 0;">Mata Kuliah yang Anda Ikuti</h3>
                <span style="font-size: 14px; color: #6E6E73;">Total: {{ $enrolledCourses->count() }} mata kuliah</span>
            </div>

            @if ($enrolledCourses->isEmpty())
                <div style="padding: 32px; background: #F5F5F7; border-radius: 16px; text-align: center;">
                    <p style="color: #6E6E73; margin: 0;">Anda belum terdaftar di mata kuliah mana pun.</p>
                </div>
            @else
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                    @foreach ($enrolledCourses as $course)
                        <div style="background: #FFFFFF; border: 1px solid #E5E5EA; border-radius: 16px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                    <span style="font-size: 12px; font-weight: 700; color: #34C759; background: #E8F8EE; padding: 4px 8px; border-radius: 6px;">{{ $course->code }}</span>
                                    <span style="font-size: 12px; color: #8E8E93;">{{ $course->sks }} SKS</span>
                                </div>
                                <h4 style="font-size: 18px; font-weight: 600; margin-bottom: 6px;">
                                    <a href="{{ route('mahasiswa.courses.show', $course) }}" style="color: #1D1D1F; border-bottom: none;">{{ $course->name }}</a>
                                </h4>
                                <p style="font-size: 13px; color: #6E6E73; margin-bottom: 16px;">Dosen: {{ $course->lecturer?->name ?? 'Belum ditentukan' }}</p>
                            </div>
                            <div style="border-top: 1px solid #F5F5F7; padding-top: 14px; font-size: 13px; color: #8E8E93; display: flex; justify-content: space-between; align-items: center;">
                                <span style="display: inline-flex; align-items: center; gap: 8px;">
                                    <span style="display: inline-flex; align-items: center; gap: 4px;">
                                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                        {{ $course->materials_count }} Materi
                                    </span>
                                    <span>·</span>
                                    <span style="display: inline-flex; align-items: center; gap: 4px;">
                                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        {{ $course->assignments_count }} Tugas
                                    </span>
                                </span>
                                <a href="{{ route('mahasiswa.courses.show', $course) }}" style="color: #0071E3; font-weight: 600; border-bottom: none;">Masuk →</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <section style="margin-bottom: 32px;">
            <p><a href="{{ route('mahasiswa.grades.index') }}">Lihat rekap nilai dan feedback saya</a></p>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="font-size: 22px; font-weight: 600; margin: 0;">Tugas Aktif</h3>
                <span style="font-size: 14px; color: #6E6E73;">Total: {{ $activeAssignmentsCount }}</span>
            </div>
            @if ($activeAssignments->isEmpty())
                <div style="padding: 24px; background: #F5F5F7; border-radius: 16px; text-align: center;">
                    <p style="color: #6E6E73; margin: 0;">Tidak ada tugas aktif dengan tenggat yang akan datang.</p>
                </div>
            @else
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    @foreach ($activeAssignments as $assignment)
                        <div style="background: #FFFFFF; border: 1px solid #E5E5EA; border-radius: 12px; padding: 18px 20px; display: flex; justify-content: space-between; align-items: center; gap: 16px;">
                            <div>
                                <h4 style="font-size: 16px; margin: 0 0 4px;">{{ $assignment->title }}</h4>
                                <p style="font-size: 13px; color: #6E6E73; margin: 0;">{{ $assignment->course->name }} · Tenggat {{ $assignment->due_at->format('d M Y, H:i') }}</p>
                            </div>
                            <a href="{{ route('mahasiswa.assignments.show', $assignment) }}" style="color: #0071E3; text-decoration: none; white-space: nowrap;">Lihat tugas →</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</x-layout>
