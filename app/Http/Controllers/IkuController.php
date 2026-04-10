<?php

namespace App\Http\Controllers;

use App\Models\Iku;
use Illuminate\Http\Request;

class IkuController extends Controller
{
    public function index()
    {
        $ikus = Iku::latest()->get();
        return view('iku.index', compact('ikus'));
    }

    public function create()
    {
        return view('iku.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required',
            'name' => 'required',
        ]);

        Iku::create([
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('iku.index')->with('success', 'IKU berhasil ditambahkan');
    }
}