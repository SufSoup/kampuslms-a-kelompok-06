<x-layout title="{{ $course->name }}">
    <div style="margin-bottom: 24px;">
        @php
            $backRoute = match(auth()->user()->role) {
                'admin' => route('admin.courses.index'),
                'dosen' => route('dosen.courses.index'),
                'mahasiswa' => route('mahasiswa.courses.index'),
                default => url('/'),
            };
        @endphp
        <a href="{{ $backRoute }}" style="font-size: 14px; color: #6E6E73; border-bottom: none;">
            ← Kembali ke Daftar Mata Kuliah
        </a>
    </div>

    <div style="background: #F5F5F7; border-radius: 16px; padding: 28px; margin-bottom: 36px; border: 1px solid #E5E5EA;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
            <div>
                <span style="font-size: 12px; font-weight: 700; color: #0071E3; background: #EBF5FF; padding: 4px 10px; border-radius: 6px;">
                    {{ $course->code }}
                </span>
                <span style="font-size: 13px; color: #8E8E93; margin-left: 10px;">{{ $course->sks }} SKS</span>
            </div>
            <span style="font-size: 12px; font-weight: 600; text-transform: uppercase; padding: 4px 10px; border-radius: 6px; background: #E5E5EA;">
                Status: {{ ucfirst($course->status) }}
            </span>
        </div>

        <h1 style="font-size: 32px; margin-bottom: 12px;">{{ $course->name }}</h1>
        <p style="color: #3A3A3C; font-size: 15px; margin-bottom: 16px;">{{ $course->description }}</p>

        <div style="font-size: 14px; color: #6E6E73; border-top: 1px solid #E5E5EA; padding-top: 14px;">
            👨‍🏫 <strong>Dosen Pengampu:</strong> {{ $course->lecturer?->name ?? 'Belum ditentukan' }}
            @if ($course->lecturer?->email)
                ({{ $course->lecturer->email }})
            @endif
        </div>
    </div>

    @can('viewAny', [\App\Models\Material::class, $course])
        @php
            $courseRoutePrefix = match (auth()->user()->role) {
                'admin' => 'admin',
                'dosen' => 'dosen',
                'mahasiswa' => 'mahasiswa',
            };
        @endphp
        <section style="margin-bottom: 36px; padding: 24px; border: 1px solid #E5E5EA; border-radius: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px;">
                <div>
                    <h2 style="font-size: 22px; margin: 0 0 6px;">Materi Perkuliahan</h2>
                    <p style="color: #6E6E73; margin: 0;">{{ $course->materials->count() }} materi tersedia untuk mata kuliah ini.</p>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <a href="{{ route($courseRoutePrefix . '.courses.materials.index', $course) }}" style="color: #0071E3; text-decoration: none;">
                        Lihat Materi
                    </a>
                    @can('create', [\App\Models\Material::class, $course])
                        <a href="{{ route($courseRoutePrefix . '.courses.materials.create', $course) }}" style="background: #000; color: #fff; padding: 9px 16px; border-radius: 999px; text-decoration: none;">
                            + Tambah Materi
                        </a>
                    @endcan
                </div>
            </div>
        </section>
    @endcan

    <!-- ================================================================= -->
    <!-- FITUR 17: KURASI SUMBER BELAJAR TAMBAHAN & PENANDA BACA          -->
    <!-- ================================================================= -->
    <div style="margin-top: 40px; border-top: 2px solid #F5F5F7; padding-top: 32px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="font-size: 22px; font-weight: 600; margin: 0;">
                    📖 Sumber Belajar Tambahan
                    <span style="font-size: 12px; font-weight: 600; color: #7952B3; background: #F3E8FF; padding: 3px 8px; border-radius: 6px; vertical-align: middle; margin-left: 6px;">
                        Fitur 17 Kelompok 06
                    </span>
                </h2>
                <p style="font-size: 14px; color: #6E6E73; margin-top: 4px; margin-bottom: 0;">
                    Kurasi referensi tambahan (artikel, video, dokumentasi) yang disediakan pengampu.
                </p>
            </div>

            @if (auth()->user()->role === 'admin' || (auth()->user()->role === 'dosen' && $course->lecturer_id === auth()->id()))
                <a href="{{ route('learning-resources.create', $course) }}" style="background: #000000; color: #FFFFFF; padding: 8px 18px; border-radius: 980px; font-size: 13px; font-weight: 500; text-decoration: none; border-bottom: none;">
                    + Tambah Sumber Belajar
                </a>
            @endif
        </div>

        @if ($course->learningResources->isEmpty())
            <div style="padding: 28px; background: #FAFAFA; border: 1px dashed #D1D1D6; border-radius: 12px; text-align: center;">
                <p style="color: #8E8E93; margin: 0; font-size: 14px;">
                    Belum ada sumber belajar tambahan yang dikurasi untuk mata kuliah ini.
                </p>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 14px;">
                @foreach ($course->learningResources as $resource)
                    @php
                        $isRead = $resource->readers->contains(auth()->id());
                    @endphp
                    <div style="background: #FFFFFF; border: 1px solid #E5E5EA; border-radius: 12px; padding: 18px 20px; display: flex; justify-content: space-between; align-items: center; gap: 16px;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; padding: 2px 7px; border-radius: 4px; background: #F2F2F7; color: #1D1D1F;">
                                    {{ ucfirst($resource->type) }}
                                </span>
                                @if ($resource->source_platform)
                                    <span style="font-size: 12px; color: #8E8E93;">• {{ $resource->source_platform }}</span>
                                @endif
                                @if ($resource->is_recommended)
                                    <span style="font-size: 11px; font-weight: 600; color: #D97706; background: #FEF3C7; padding: 2px 7px; border-radius: 4px;">
                                        ⭐ Direkomendasikan
                                    </span>
                                @endif
                            </div>

                            <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 4px 0;">
                                <a href="{{ $resource->url }}" target="_blank" rel="noopener noreferrer" style="color: #0071E3; text-decoration: none; border-bottom: 1px solid #0071E3;">
                                    {{ $resource->title }} ↗
                                </a>
                            </h3>

                            @if ($resource->description)
                                <p style="font-size: 13px; color: #6E6E73; margin: 4px 0 0 0;">{{ $resource->description }}</p>
                            @endif

                            <div style="font-size: 12px; color: #8E8E93; margin-top: 6px;">
                                👥 {{ $resource->readers->count() }} mahasiswa telah menandai sudah membaca
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 10px;">
                            @if (auth()->user()->role === 'mahasiswa')
                                <!-- Form Penanda Baca Mahasiswa (Anti-IDOR) -->
                                <form method="POST" action="{{ route('learning-resources.toggle-read', $resource) }}" style="margin: 0;">
                                    @csrf
                                    @if ($isRead)
                                        <button type="submit" style="background: #E8F8EE; color: #166534; border: 1px solid #86EFAC; padding: 6px 14px; font-size: 13px; font-weight: 600; border-radius: 980px;">
                                            ✓ Sudah Dibaca
                                        </button>
                                    @else
                                        <button type="submit" style="background: #F5F5F7; color: #1D1D1F; border: 1px solid #D1D1D6; padding: 6px 14px; font-size: 13px; font-weight: 500; border-radius: 980px;">
                                            Tandai Selesai Baca
                                        </button>
                                    @endif
                                </form>
                            @endif

                            @if (auth()->user()->role === 'admin' || (auth()->user()->role === 'dosen' && $course->lecturer_id === auth()->id()))
                                <a href="{{ route('learning-resources.edit', [$course, $resource]) }}" style="font-size: 13px; color: #0071E3; padding: 6px 12px; border: 1px solid #D1D1D6; border-radius: 980px; text-decoration: none;">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('learning-resources.destroy', [$course, $resource]) }}" style="margin: 0;" onsubmit="return confirm('Hapus sumber belajar ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: transparent; color: #DC2626; border: 1px solid #FCA5A5; padding: 6px 12px; font-size: 13px; border-radius: 980px;">
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    @if (auth()->user()->role === 'admin')
        <section>
            <h2>Mahasiswa Terdaftar</h2>

            @if ($course->students->isEmpty())
                <p>Belum ada mahasiswa yang terdaftar.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($course->students as $student)
                            <tr>
                                <td>{{ $student->name }}</td>
                                <td>{{ $student->email }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.courses.enrollments.destroy', [$course, $student]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit">Hapus Pendaftaran</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($availableStudents->isNotEmpty())
                <form method="POST" action="{{ route('admin.courses.enrollments.store', $course) }}">
                    @csrf
                    <label for="student_id">Daftarkan mahasiswa</label>
                    <select id="student_id" name="student_id" required>
                        <option value="">Pilih mahasiswa</option>
                        @foreach ($availableStudents as $student)
                            <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>
                                {{ $student->name }} ({{ $student->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('student_id')
                        <p role="alert">{{ $message }}</p>
                    @enderror
                    <button type="submit">Daftarkan</button>
                </form>
            @endif
        </section>
    @endif

    @can('update', $course)
        <a href="{{ route('admin.courses.edit', $course) }}">Edit</a>
    @endcan
</x-layout>
