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

        <div style="font-size: 14px; color: #6E6E73; border-top: 1px solid #E5E5EA; padding-top: 14px; display: flex; align-items: center; gap: 6px;">
            <svg style="width: 16px; height: 16px; color: #6E6E73;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            <span><strong>Dosen Pengampu:</strong> {{ $course->lecturer?->name ?? 'Belum ditentukan' }}</span>
            @if ($course->lecturer?->email)
                <span>({{ $course->lecturer->email }})</span>
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
                <h2 style="font-size: 22px; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <svg style="width: 20px; height: 20px; color: #0071E3;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    <span>Sumber Belajar Tambahan</span>
                    <span style="font-size: 12px; font-weight: 600; color: #7952B3; background: #F3E8FF; padding: 3px 8px; border-radius: 6px; vertical-align: middle;">
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
                                    <span style="font-size: 11px; font-weight: 600; color: #D97706; background: #FEF3C7; padding: 2px 7px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">
                                        <svg style="width: 12px; height: 12px;" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                        Direkomendasikan
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

                            <div style="font-size: 12px; color: #8E8E93; margin-top: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                <span>{{ $resource->readers->count() }} mahasiswa telah menandai sudah membaca</span>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 10px;">
                            @if (auth()->user()->role === 'mahasiswa')
                                <!-- Form Penanda Baca Mahasiswa (Anti-IDOR) -->
                                <form method="POST" action="{{ route('learning-resources.toggle-read', $resource) }}" style="margin: 0;">
                                    @csrf
                                    @if ($isRead)
                                        <button type="submit" style="background: #E8F8EE; color: #166534; border: 1px solid #86EFAC; padding: 6px 14px; font-size: 13px; font-weight: 600; border-radius: 980px; display: inline-flex; align-items: center; gap: 4px;">
                                            <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                            <span>Sudah Dibaca</span>
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
