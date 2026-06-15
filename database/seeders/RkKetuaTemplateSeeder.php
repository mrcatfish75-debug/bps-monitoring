<?php

namespace Database\Seeders;

use App\Imports\RkKetuaTemplateImport;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class RkKetuaTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $path = $this->resolveImportPath([
            storage_path('app/imports/rk-ketua.xlsx'),
            storage_path('app/import/rk-ketua.xlsx'),
            storage_path('app/imports/RkKetua.xlsx'),
            storage_path('app/import/RkKetua.xlsx'),
            storage_path('app/imports/RK Ketua.xlsx'),
            storage_path('app/import/RK Ketua.xlsx'),
        ]);

        if (!$path) {
            $this->command?->warn('Seeder RK Ketua Template dilewati.');
            $this->command?->warn('File template RK Ketua tidak ditemukan.');
            $this->command?->warn('Letakkan file pada salah satu lokasi berikut:');
            $this->command?->warn('- storage/app/imports/rk-ketua.xlsx');
            $this->command?->warn('- storage/app/import/rk-ketua.xlsx');
            $this->command?->warn('- storage/app/imports/RkKetua.xlsx');
            $this->command?->warn('- storage/app/import/RkKetua.xlsx');

            return;
        }

        $import = new RkKetuaTemplateImport();

        Excel::import($import, $path);

        $this->command?->info('Import template RK Ketua selesai.');
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