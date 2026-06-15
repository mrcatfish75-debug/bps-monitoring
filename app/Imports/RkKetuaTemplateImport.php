<?php

namespace App\Imports;

use App\Models\RkKetuaTemplate;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RkKetuaTemplateImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        /*
        |--------------------------------------------------------------------------
        | Format Excel:
        |--------------------------------------------------------------------------
        | No | Rencana Kerja Ketua
        |
        | Laravel Excel biasanya mengubah heading:
        | "No" menjadi "no"
        | "Rencana Kerja Ketua" menjadi "rencana_kerja_ketua"
        |--------------------------------------------------------------------------
        */

        $description = $this->getDescription($row);

        if ($description === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Simpan ke tabel rk_ketua_templates
        |--------------------------------------------------------------------------
        | updateOrCreate dipakai agar import ulang file yang sama tidak membuat
        | data template dobel.
        |--------------------------------------------------------------------------
        */

        RkKetuaTemplate::updateOrCreate(
            [
                'description' => $description,
            ],
            [
                'category' => 'RK Ketua',
                'is_active' => true,
            ]
        );

        return null;
    }

    private function getDescription(array $row): string
    {
        $possibleKeys = [
            'rencana_kerja_ketua',
            'rencana kerja ketua',
            'rencana_kerja',
            'rencana kerja',
            'rencana_kinerja_ketua',
            'rencana kinerja ketua',
            'description',
            'deskripsi',
        ];

        foreach ($possibleKeys as $key) {
            if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
                return trim((string) $row[$key]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback berdasarkan urutan kolom
        |--------------------------------------------------------------------------
        | Jika heading Excel tidak terbaca normal, kolom kedua biasanya adalah
        | Rencana Kerja Ketua. Ini menjaga import tetap aman.
        |--------------------------------------------------------------------------
        */

        $values = array_values($row);

        if (isset($values[1]) && trim((string) $values[1]) !== '') {
            return trim((string) $values[1]);
        }

        return '';
    }
}