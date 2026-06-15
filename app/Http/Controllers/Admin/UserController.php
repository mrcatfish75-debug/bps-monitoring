<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\UserImport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

        // SORTING AMAN
        $allowedSorts = ['name', 'email', 'nip', 'role', 'created_at'];

        if ($request->sort && in_array($request->sort, $allowedSorts, true)) {
            $direction = $request->direction === 'desc' ? 'desc' : 'asc';
            $query->orderBy($request->sort, $direction);
        } else {
            $query->latest();
        }

        $users = $query->paginate(10)->withQueryString();

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
            'name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:255|unique:users,nip',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => 'required|in:admin,kepala,ketua,anggota',
            'team_id' => 'nullable|exists:teams,id',
        ]);

        $temporaryPassword = $this->generateTemporaryPassword();

        $user = User::create([
            'name' => $request->name,
            'nip' => $request->nip,
            'email' => $request->email,
            'password' => Hash::make($temporaryPassword),
            'plain_password' => null,
            'is_default_password' => true,
            'password_reset_at' => now(),
            'password_reset_by' => auth()->id(),
            'role' => $request->role,
            'team_id' => $request->team_id,
        ]);

        if (function_exists('logActivity')) {
            logActivity('create', 'User', $user->id, 'Buat user dengan password sementara');
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil dibuat. Password sementara hanya ditampilkan sekali.')
            ->with('temporary_password', $temporaryPassword)
            ->with('temporary_password_user', $user->name)
            ->with('temporary_password_email', $user->email);
    }

    public function edit(User $user)
    {
        $teams = Team::all();

        return view('admin.user.edit', compact('user', 'teams'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:255|unique:users,nip,' . $user->id,
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,kepala,ketua,anggota',
            'team_id' => 'nullable|exists:teams,id',
        ]);

        $user->update([
            'name' => $request->name,
            'nip' => $request->nip,
            'email' => $request->email,
            'role' => $request->role,
            'team_id' => $request->team_id,
        ]);

        if (function_exists('logActivity')) {
            logActivity('update', 'User', $user->id, 'Update user');
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User berhasil diupdate.');
    }

    public function destroy(User $user)
    {
        if ((int) auth()->id() === (int) $user->id) {
            return back()->with('error', 'Kamu tidak bisa menghapus akunmu sendiri.');
        }

        $userId = $user->id;

        $user->delete();

        if (function_exists('logActivity')) {
            logActivity('delete', 'User', $userId, 'Hapus user');
        }

        return back()->with('success', 'User berhasil dihapus.');
    }

    public function resetPassword($id)
    {
        $user = User::findOrFail($id);

        if ((int) auth()->id() === (int) $user->id) {
            return back()->with('error', 'Kamu tidak bisa mereset password akunmu sendiri dari halaman ini.');
        }

        $temporaryPassword = $this->generateTemporaryPassword();

        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'plain_password' => null,
            'is_default_password' => true,
            'password_reset_at' => now(),
            'password_reset_by' => auth()->id(),
            'remember_token' => Str::random(60),
        ])->save();

        if (function_exists('logActivity')) {
            logActivity('reset_password', 'User', $user->id, 'Reset password user');
        }

        return back()
            ->with('success', 'Password berhasil direset. Password sementara hanya ditampilkan sekali.')
            ->with('temporary_password', $temporaryPassword)
            ->with('temporary_password_user', $user->name)
            ->with('temporary_password_email', $user->email);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        $rows = Excel::toArray([], $request->file('file'))[0];

        $createdUsers = [];
        $skipped = 0;

        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $name = trim($row[0] ?? '');
            $nip = trim($row[1] ?? '');
            $email = trim($row[2] ?? '');

            if (!$name || !$email) {
                $skipped++;
                continue;
            }

            if (User::where('email', $email)->exists()) {
                $skipped++;
                continue;
            }

            if ($nip && User::where('nip', $nip)->exists()) {
                $skipped++;
                continue;
            }

            $temporaryPassword = $this->generateTemporaryPassword();

            $user = User::create([
                'name' => $name,
                'nip' => $nip ?: null,
                'email' => $email,
                'password' => Hash::make($temporaryPassword),
                'plain_password' => null,
                'is_default_password' => true,
                'password_reset_at' => now(),
                'password_reset_by' => auth()->id(),
                'role' => User::ROLE_ANGGOTA,
                'team_id' => null,
            ]);

            $createdUsers[] = [
                'name' => $user->name,
                'email' => $user->email,
                'password' => $temporaryPassword,
            ];
        }

        if (function_exists('logActivity')) {
            logActivity('import', 'User', null, 'Import user');
        }

        return back()
            ->with('success', 'Import selesai. User baru wajib mengganti password setelah login.')
            ->with('temporary_passwords', $createdUsers)
            ->with('import_skipped', $skipped);
    }

    private function generateTemporaryPassword(int $length = 12): string
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $numbers = '23456789';
        $symbols = '@#$%&*!?';

        $password = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $numbers[random_int(0, strlen($numbers) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        $all = $upper . $lower . $numbers . $symbols;

        while (count($password) < $length) {
            $password[] = $all[random_int(0, strlen($all) - 1)];
        }

        shuffle($password);

        return implode('', $password);
    }
}