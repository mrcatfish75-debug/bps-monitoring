<?php

namespace Database\Seeders;

use App\Imports\RkAnggotaTemplateImport;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class RkAnggotaTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $path = $this->resolveImportPath([
            storage_path('app/imports/RkAnggota.xlsx'),
            storage_path('app/import/RkAnggota.xlsx'),
            storage_path('app/imports/rk-anggota.xlsx'),
            storage_path('app/import/rk-anggota.xlsx'),
        ]);

        if (!$path) {
            $this->command?->warn('Seeder RK Anggota Template dilewati.');
            $this->command?->warn('File template RK Anggota tidak ditemukan.');
            $this->command?->warn('Letakkan file pada salah satu lokasi berikut:');
            $this->command?->warn('- storage/app/imports/RkAnggota.xlsx');
            $this->command?->warn('- storage/app/import/RkAnggota.xlsx');
            $this->command?->warn('- storage/app/imports/rk-anggota.xlsx');
            $this->command?->warn('- storage/app/import/rk-anggota.xlsx');

            return;
        }

        $import = new RkAnggotaTemplateImport();

        Excel::import($import, $path);

        $this->command?->info('Import template RK Anggota selesai.');
        $this->command?->info("File: {$path}");
        $this->command?->info("Diproses: {$import->imported}");
        $this->command?->info("Dilewati: {$import->skipped}");
    }

    private function resolveImportPath(array $paths): ?string
    {
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}