<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
{
    $query = Team::with(['leader','members']);

    // 🔥 SEARCH (INI YANG KURANG)
    if ($request->search) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%$search%")
              ->orWhereHas('leader', function ($q2) use ($search) {
                  $q2->where('name', 'like', "%$search%");
              });
        });
    }

    $teams = $query->latest()->paginate(10);

    $users = User::whereIn('role', ['ketua_tim','anggota'])->get();

    return view('admin.team.index', compact('teams','users'));
}

    public function edit(Team $team)
    {
        $users = User::whereIn('role', ['ketua_tim', 'anggota'])->get();
        return view('admin.team.edit', compact('team', 'users'));
    }

    public function update(Request $request, Team $team)
    {
        $request->validate([
            'name' => 'required',
            'leader_id' => [
                'required',
                'exists:users,id',
                function ($attr, $value, $fail) {
                    $user = User::find($value);
                    if (!in_array($user->role, ['ketua_tim', 'anggota'])) {
                        $fail('User tidak valid sebagai leader');
                    }
                }
            ],
            'members' => 'nullable|array',
            'members.*' => [
                'exists:users,id',
                function ($attr, $value, $fail) {
                    $user = User::find($value);
                    if (!in_array($user->role, ['ketua_tim', 'anggota'])) {
                        $fail('User tidak valid sebagai anggota');
                    }
                }
            ]
        ]);

        $team->update([
            'name' => $request->name,
            'leader_id' => $request->leader_id,
        ]);

        $members = $request->members ?? [];

        if (!in_array($request->leader_id, $members)) {
            $members[] = $request->leader_id;
        }

 
        $team->members()->sync($members);

        foreach ($members as $userId) {
            $user = User::find($userId);

            if (!$user->team_id) {
                $user->update(['team_id' => $team->id]);
            }
        }
        

        return redirect()->route('admin.team.index')
            ->with('success', 'Tim berhasil diupdate');
    }

    public function destroy(Team $team)
    {
        User::where('team_id', $team->id)->update([
            'team_id' => null
        ]);

        $team->delete();

        return back()->with('success', 'Tim dihapus');
    }
}