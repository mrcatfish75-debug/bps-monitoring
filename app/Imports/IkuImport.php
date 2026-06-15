<?php

namespace App\Imports;

use App\Models\Iku;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class IkuImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        /*
        |--------------------------------------------------------------------------
        | Format Excel wajib:
        |--------------------------------------------------------------------------
        | name | year | satuan | target
        |
        | Contoh:
        | Persentase publikasi statistik tepat waktu | 2026 | % | 100
        |--------------------------------------------------------------------------
        */

        $name = trim((string) ($row['name'] ?? ''));
        $year = trim((string) ($row['year'] ?? ''));
        $satuan = trim((string) ($row['satuan'] ?? ''));
        $target = $row['target'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Validasi baris
        |--------------------------------------------------------------------------
        | Jika salah satu kolom utama kosong, baris tidak diimport.
        | Catatan: target 0 tetap dianggap valid, jadi tidak memakai empty().
        |--------------------------------------------------------------------------
        */

        if (
            $name === '' ||
            $year === '' ||
            $satuan === '' ||
            $target === null ||
            $target === ''
        ) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi tahun
        |--------------------------------------------------------------------------
        */

        if (!is_numeric($year)) {
            return null;
        }

        $year = (int) $year;

        /*
        |--------------------------------------------------------------------------
        | Bersihkan target
        |--------------------------------------------------------------------------
        | Mendukung:
        | 100
        | 100%
        | 95,5
        |--------------------------------------------------------------------------
        */

        if (is_string($target)) {
            $target = str_replace('%', '', $target);
            $target = str_replace(',', '.', $target);
            $target = trim($target);
        }

        if (!is_numeric($target)) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Simpan ke database
        |--------------------------------------------------------------------------
        | updateOrCreate dipakai agar import file yang sama tidak membuat data dobel.
        | Kunci unik logis: name + year.
        |--------------------------------------------------------------------------
        */

        Iku::updateOrCreate(
            [
                'name' => $name,
                'year' => $year,
            ],
            [
                'satuan' => $satuan,
                'target' => $target,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Return null karena data sudah disimpan lewat updateOrCreate.
        | Ini mencegah Laravel Excel mencoba membuat model kedua.
        |--------------------------------------------------------------------------
        */

        return null;
    }
}