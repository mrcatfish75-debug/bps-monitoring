<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\IkuImport;
use Maatwebsite\Excel\Facades\Excel;

class IkuImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv'
        ]);

        Excel::import(new IkuImport, $request->file('file'));

        return back()->with('success', 'IKU berhasil diimport');
    }
}
