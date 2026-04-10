<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        $teams = Team::with('leader')->get();
        return view('team.index', compact('teams'));
    }

    public function create()
    {
        $users = User::all();
        return view('team.create', compact('users'));
    }

    public function store(Request $request)
    {
        $team = Team::create([
            'name' => $request->name,
            'leader_id' => $request->leader_id
        ]);

        // assign anggota
        if ($request->members) {
            $team->members()->attach($request->members);
        }

        return redirect()->route('team.index');
    }
}