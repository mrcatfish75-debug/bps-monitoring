<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Imports\UserImport;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    public function index(Request $request)
{
    $query = User::with('team');

    // SEARCH
    if ($request->search) {
        $query->where(function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->search . '%')
              ->orWhere('email', 'like', '%' . $request->search . '%')
              ->orWhere('nip', 'like', '%' . $request->search . '%');
        });
    }

    // FILTER ROLE
    if ($request->role) {
        $query->where('role', $request->role);
    }

    // 🔥 SORTING (AMAN)
    $allowedSorts = ['name', 'email', 'nip', 'role', 'created_at'];

    if ($request->sort && in_array($request->sort, $allowedSorts)) {
        $direction = $request->direction === 'desc' ? 'desc' : 'asc';
        $query->orderBy($request->sort, $direction);
    } else {
        $query->latest();
    }

    $users = $query->paginate(10);

    $teams = Team::all();

    return view('admin.user.index', compact('users', 'teams'));
}

    public function create()
    {
        $teams = Team::all();
        return view('admin.user.create', compact('teams'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'nip' => 'required|unique:users,nip',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required'
        ]);

        User::create([
            'name' => $request->name,
            'nip' => $request->nip,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
            'team_id' => $request->team_id
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dibuat');
    }

    public function edit(User $user)
    {
        $teams = Team::all();
        return view('admin.user.edit', compact('user', 'teams'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required',
            'nip' => 'required|unique:users,nip,'.$user->id,
            'email' => 'required|email|unique:users,email,'.$user->id,
        ]);

        $user->update([
            'name' => $request->name,
            'nip' => $request->nip,
            'email' => $request->email,
            'role' => $request->role,
            'team_id' => $request->team_id
        ]);

        logActivity('update', 'User', $user->id, 'Update user');

        return redirect()->route('admin.users.index')->with('success', 'User diupdate');
    }

    public function destroy(User $user)
    {
        $user->delete();

        logActivity('delete', 'User', $user->id, 'Hapus user');
        
        return back()->with('success', 'User dihapus');
    }

    public function resetPassword($id)
{
    $user = User::findOrFail($id);

    $newPassword = \Illuminate\Support\Str::random(8);

    $user->update([
        'password' => bcrypt($newPassword),
        'plain_password' => null,
        'is_default_password' => false
    ]);

    return back()->with('success', 'Password direset');
}


public function import(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,csv'
    ]);

    $rows = Excel::toArray([], $request->file('file'))[0];

    foreach ($rows as $index => $row) {

        if ($index == 0) continue; // skip header

        $name = trim($row[0] ?? '');;
        $nip = $row[1] ?? null;
        $email = $row[2] ?? null;

        if (!$name || !$email) continue;

        // 🔥 AMBIL NAMA DEPAN
        $firstName = explode(' ', $name)[0];

        // 🔥 PASSWORD = nama depan + 123
        $plainPassword = $firstName . '123';

        // 🔥 CEK EMAIL DUPLIKAT
        if (\App\Models\User::where('email', $email)->exists()) {
            continue;
        }

        \App\Models\User::create([
            'name' => $name,
            'nip' => $nip,
            'email' => $email,

            'password' => Hash::make($plainPassword),
            'plain_password' => $plainPassword,
            'is_default_password' => 1,

            'role' => 'anggota',
        ]);
    }

    return back()->with('success', 'User berhasil diimport!');
}


}