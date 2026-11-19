<x-layout title="{{ $course->name }} — KampusLMS">
    @php
        $backRoute = match(auth()->user()->role) {
            'admin' => route('admin.courses.index'),
            'dosen' => route('dosen.courses.index'),
            'mahasiswa' => route('mahasiswa.courses.index'),
            default => url('/'),
        };
    @endphp

    <div class="breadcrumb">
        <a href="{{ $backRoute }}"><x-icon name="arrow-left" class="icon-sm" /> Daftar Mata Kuliah</a>
        <x-icon name="chevron-right" class="icon-sm" />
        <span>{{ $course->code }}</span>
    </div>

    <div class="profile-card">
        <div class="profile-top">
            <div class="profile-chips">
                <span class="chip">{{ $course->code }}</span>
                <span class="badge badge-neutral">{{ $course->sks }} SKS</span>
            </div>
            <span class="badge badge-{{ $course->status === 'active' ? 'success' : ($course->status === 'draft' ? 'warning' : 'neutral') }}">
                Status: {{ ucfirst($course->status) }}
            </span>
        </div>

        <h1 class="profile-title">{{ $course->name }}</h1>
        <p class="profile-desc">{{ $course->description }}</p>

        <div class="profile-meta">
            <span>
                <x-icon name="user" class="icon-sm" />
                <strong>Dosen Pengampu:</strong> {{ $course->lecturer?->name ?? 'Belum ditentukan' }}
                @if ($course->lecturer?->email)
                    ({{ $course->lecturer->email }})
                @endif
            </span>
            @if (auth()->user()->role === 'dosen' && $course->lecturer_id === auth()->id())
                <span>
                    <x-icon name="award" class="icon-sm" />
                    <a href="{{ route('dosen.courses.grades.index', $course) }}">Rekap nilai</a>
                </span>
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
        <section class="section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Materi Perkuliahan</h2>
                    <p class="section-desc">{{ $course->materials->count() }} materi tersedia untuk mata kuliah ini.</p>
                </div>
                <div class="item-actions">
                    <a href="{{ route($courseRoutePrefix . '.courses.materials.index', $course) }}" class="btn btn-secondary btn-sm">
                        Lihat Materi
                    </a>
                    @can('create', [\App\Models\Material::class, $course])
                        <a href="{{ route($courseRoutePrefix . '.courses.materials.create', $course) }}" class="btn btn-primary btn-sm">
                            <x-icon name="plus" class="icon-sm" /> Tambah Materi
                        </a>
                    @endcan
                </div>
            </div>
        </section>
    @endcan

    <!-- ================================================================= -->
    <!-- FITUR 17: KURASI SUMBER BELAJAR TAMBAHAN & PENANDA BACA          -->
    <!-- ================================================================= -->
    <section class="section">
        <div class="section-header">
            <div>
                <h2 class="section-title">
                    Sumber Belajar Tambahan
                    <span class="badge badge-purple">Fitur 17 Kelompok 06</span>
                </h2>
                <p class="section-desc">
                    Kurasi referensi tambahan (artikel, video, dokumentasi) yang disediakan pengampu.
                </p>
            </div>

            @if (auth()->user()->role === 'admin' || (auth()->user()->role === 'dosen' && $course->lecturer_id === auth()->id()))
                <a href="{{ route('learning-resources.create', $course) }}" class="btn btn-primary btn-sm">
                    <x-icon name="plus" class="icon-sm" /> Tambah Sumber Belajar
                </a>
            @endif
        </div>

        @if ($course->learningResources->isEmpty())
            <div class="empty-state">
                <x-icon name="link" class="icon" />
                <span>Belum ada sumber belajar tambahan yang dikurasi untuk mata kuliah ini.</span>
            </div>
        @else
            <div class="item-list">
                @foreach ($course->learningResources as $resource)
                    @php
                        $isRead = $resource->readers->contains(auth()->id());
                    @endphp
                    <div class="item-card">
                        <div class="item-main">
                            <div class="item-eyebrow">
                                <span class="badge badge-neutral">{{ ucfirst($resource->type) }}</span>
                                @if ($resource->source_platform)
                                    <span style="text-transform: none;">{{ $resource->source_platform }}</span>
                                @endif
                                @if ($resource->is_recommended)
                                    <span class="badge badge-warning"><x-icon name="star" class="icon-sm" /> Direkomendasikan</span>
                                @endif
                            </div>

                            <h3 class="item-title">
                                <a href="{{ $resource->url }}" target="_blank" rel="noopener noreferrer">
                                    {{ $resource->title }} <x-icon name="external" class="icon-sm" />
                                </a>
                            </h3>

                            @if ($resource->description)
                                <p class="item-desc">{{ $resource->description }}</p>
                            @endif

                            @if (auth()->user()->role !== 'mahasiswa')
                                <div class="item-meta">
                                    <span><x-icon name="check-circle" class="icon-sm" /> {{ $resource->readers->count() }} mahasiswa telah menandai sudah membaca</span>
                                </div>
                            @endif
                        </div>

                        <div class="item-actions">
                            @if (auth()->user()->role === 'mahasiswa')
                                <!-- Form Penanda Baca Mahasiswa (Anti-IDOR) -->
                                <form method="POST" action="{{ route('learning-resources.toggle-read', $resource) }}" style="margin: 0;">
                                    @csrf
                                    @if ($isRead)
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            <x-icon name="check" class="icon-sm" /> Sudah Dibaca
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            Tandai Selesai Baca
                                        </button>
                                    @endif
                                </form>
                            @endif

                            @if (auth()->user()->role === 'admin' || (auth()->user()->role === 'dosen' && $course->lecturer_id === auth()->id()))
                                <a href="{{ route('learning-resources.edit', [$course, $resource]) }}" class="btn btn-secondary btn-sm">
                                    <x-icon name="edit" class="icon-sm" /> Edit
                                </a>
                                <form method="POST" action="{{ route('learning-resources.destroy', [$course, $resource]) }}" style="margin: 0;" onsubmit="return confirm('Hapus sumber belajar ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <x-icon name="trash" class="icon-sm" /> Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @if (auth()->user()->role === 'admin')
        <section class="section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Mahasiswa Terdaftar</h2>
                    <p class="section-desc">{{ $course->students->count() }} mahasiswa terdaftar pada mata kuliah ini.</p>
                </div>
            </div>

            @if ($course->students->isEmpty())
                <div class="empty-state">
                    <x-icon name="users" class="icon" />
                    <span>Belum ada mahasiswa yang terdaftar.</span>
                </div>
            @else
                <div class="table-card">
                    <div class="table-wrap">
                        <table class="data-table">
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
                                        <td class="cell-strong">{{ $student->name }}</td>
                                        <td class="cell-muted">{{ $student->email }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.courses.enrollments.destroy', [$course, $student]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <x-icon name="trash" class="icon-sm" /> Hapus Pendaftaran
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($availableStudents->isNotEmpty())
                <div class="card" style="margin-top: 20px;">
                    <form method="POST" action="{{ route('admin.courses.enrollments.store', $course) }}" class="form">
                        @csrf
                        <div class="field">
                            <label for="student_id">Daftarkan mahasiswa</label>
                            <select id="student_id" name="student_id" class="input {{ $errors->has('student_id') ? 'is-invalid' : '' }}" required>
                                <option value="">Pilih mahasiswa</option>
                                @foreach ($availableStudents as $student)
                                    <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>
                                        {{ $student->name }} ({{ $student->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('student_id')
                                <p class="field-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <x-icon name="plus" class="icon-sm" /> Daftarkan
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </section>
    @endif

    @can('update', $course)
        <div class="form-actions">
            <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-secondary">
                <x-icon name="edit" class="icon-sm" /> Edit Mata Kuliah
            </a>
        </div>
    @endcan
</x-layout>
