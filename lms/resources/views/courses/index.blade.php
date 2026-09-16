-- Active: 1758692748681@@127.0.0.1@3306
<x-layout title="Daftar Mata Kuliah">
    <h1>Daftar Mata Kuliah</h1>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    <a href="{{ route('courses.create') }}">Tambah Mata Kuliah</a>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>SKS</th>
                <th>Dosen</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($courses as $course)
                <tr>
                    <td>{{ $course->code }}</td>
                    <td>
                        <a href="{{ route('courses.show', $course) }}">{{ $course->name }}</a>
                    </td>
                    <td>{{ $course->sks }}</td>
                    <td>{{ $course->lecturer?->name ?? 'Dosen tidak tersedia' }}</td>
                    <td>
                        <a href="{{ route('courses.edit', $course) }}">Edit</a>
                        <form method="POST" action="{{ route('courses.destroy', $course) }}" style="display: inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-layout>