<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->get();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'nim_nip' => ['nullable', 'string', 'max:255', 'unique:users,nim_nip'],
            'role' => ['required', Rule::in(['admin', 'dosen', 'mahasiswa'])],
        ]);

        $role = $validated['role'];
        unset($validated['role']);

        $user = new User();
        $user->fill($validated);
        $user->role = $role;
        $user->save();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dibuat.');
    }

    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'nim_nip' => ['nullable', 'string', 'max:255', Rule::unique('users', 'nim_nip')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'dosen', 'mahasiswa'])],
        ]);

        $role = $validated['role'];
        unset($validated['role']);

        if ($validated['password'] === null || $validated['password'] === '') {
            unset($validated['password']);
        }

        $user->fill($validated);
        $user->role = $role;
        $user->save();

        return redirect()->route('users.show', $user)->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}