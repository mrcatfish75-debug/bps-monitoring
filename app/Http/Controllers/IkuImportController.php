<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\IkuImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class IkuImportController extends Controller
{
    /**
     * Import IKU dari file Excel
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function import(Request $request)
    {
        // Validasi file
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ], [
            'file.required' => 'File Excel IKU wajib diunggah.',
            'file.mimes' => 'File harus berupa XLSX atau CSV.',
        ]);

        try {
            Excel::import(new IkuImport, $request->file('file'));

            return redirect()
                ->back()
                ->with('success', 'IKU berhasil diimport dari Excel.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // Menangkap error validasi dari Excel
            $failures = $e->failures();

            $messages = [];
            foreach ($failures as $failure) {
                $messages[] = "Baris {$failure->row()}: " . implode(', ', $failure->errors());
            }

            return redirect()
                ->back()
                ->with('error', 'Import gagal. Detail: ' . implode(' | ', $messages));
        } catch (\Exception $e) {
            // Log error dan tampilkan message umum
            Log::error('IKU Import Error: ' . $e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'Terjadi kesalahan saat mengimport IKU. Silakan coba lagi.');
        }
    }
}