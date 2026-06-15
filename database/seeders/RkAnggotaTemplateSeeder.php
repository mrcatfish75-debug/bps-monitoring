<?php

namespace Database\Seeders;

use App\Imports\RkAnggotaTemplateImport;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class RkAnggotaTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $path = storage_path('app/imports/RkAnggota.xlsx');

        if (!file_exists($path)) {
            $this->command?->error("File tidak ditemukan: {$path}");
            $this->command?->warn('Pastikan file RkAnggota.xlsx diletakkan di storage/app/imports/RkAnggota.xlsx');
            return;
        }

        $import = new RkAnggotaTemplateImport();

        Excel::import($import, $path);

        $this->command?->info("Import template RK Anggota selesai.");
        $this->command?->info("Diproses: {$import->imported}");
        $this->command?->info("Dilewati: {$import->skipped}");
    }
}