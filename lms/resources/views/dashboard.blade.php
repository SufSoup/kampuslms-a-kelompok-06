<x-layout title="Dashboard — KampusLMS">
    <div style="margin-bottom: 32px;">
        <h1 style="margin-bottom: 8px;">Dashboard</h1>
        <p style="color: #6E6E73;">
            Selamat datang di portal akademik KampusLMS, <strong>{{ auth()->user()->name }}</strong>.
        </p>
    </div>

    @if (auth()->user()->role === 'admin')
        <!-- Dashboard Admin -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px;">
            <div style="background: #F5F5F7; padding: 24px; border-radius: 16px; border: 1px solid #E5E5EA;">
                <p style="font-size: 13px; font-weight: 600; text-transform: uppercase; color: #6E6E73; margin-bottom: 8px;">Peran</p>
                <h2 style="font-size: 28px; font-weight: 700; margin: 0; color: #0071E3;">Administrator</h2>
                <small style="color: #8E8E93;">Hak akses penuh ke seluruh modul sistem</small>
            </div>
            <div style="background: #F5F5F7; padding: 24px; border-radius: 16px; border: 1px solid #E5E5EA;">
                <p style="font-size: 13px; font-weight: 600; text-transform: uppercase; color: #6E6E73; margin-bottom: 8px;">Aksi Cepat</p>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="{{ route('admin.courses.index') }}" style="border-bottom: none; color: #0071E3; font-weight: 500;">→ Kelola Mata Kuliah</a>
                    <a href="{{ route('admin.users.index') }}" style="border-bottom: none; color: #0071E3; font-weight: 500;">→ Kelola Pengguna</a>
                </div>
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
        @php
            $taughtCourses = auth()->user()->taughtCourses()->withCount(['students', 'materials', 'assignments'])->get();
        @endphp

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
                                <span>👥 {{ $course->students_count }} Mahasiswa</span>
                                <a href="{{ route('dosen.courses.show', $course) }}" style="color: #0071E3; font-weight: 600; border-bottom: none;">Buka Kelas →</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    @elseif (auth()->user()->role === 'mahasiswa')
        <!-- Dashboard Mahasiswa -->
        @php
            $enrolledCourses = auth()->user()->courses()->with('lecturer')->withCount(['materials', 'assignments'])->get();
        @endphp

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
                                <span>📚 {{ $course->materials_count }} Materi · 📝 {{ $course->assignments_count }} Tugas</span>
                                <a href="{{ route('mahasiswa.courses.show', $course) }}" style="color: #0071E3; font-weight: 600; border-bottom: none;">Masuk →</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</x-layout>
