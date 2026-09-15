-- Active: 1758692748681@@127.0.0.1@3306
<x-layout title="Daftar Mata Kuliah">
    <p>{{    $nama }}</p>
    <h1>Daftar Mata Kuliah</h1>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>SKS</th>
                <th>Dosen</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($courses as $course)
                <tr>
                    <td>{{ $course['code'] }}</td>
                    <td>
                        <a href="{{ route('courses.show', $course['id']) }}">{{ $course['name'] }}</a>
                    </td>
                    <td>{{ $course['sks'] }}</td>
                    <td>{{ $course['lecturer'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-layout>