<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Team;
use App\Models\Iku;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with(['iku', 'team', 'leader'])->get();
        return view('project.index', compact('projects'));
    }

    public function create()
    {
        $teams = Team::all();
        $ikus = Iku::all();
        $users = User::all();

        return view('project.create', compact('teams', 'ikus', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'iku_id' => 'required',
            'team_id' => 'required',
            'leader_id' => 'required',
        ]);

        $project = Project::create([
            'name' => $request->name,
            'iku_id' => $request->iku_id,
            'team_id' => $request->team_id,
            'leader_id' => $request->leader_id,
        ]);

        if ($request->members) {
            $project->members()->attach($request->members);
        }

        return redirect()->route('project.index');
    }
}
