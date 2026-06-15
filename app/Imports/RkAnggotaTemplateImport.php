<?php

namespace App\Imports;

use App\Models\RkAnggotaTemplate;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class RkAnggotaTemplateImport implements ToCollection
{
    public int $imported = 0;
    public int $skipped = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            /*
            |--------------------------------------------------------------------------
            | Ambil kolom pertama
            |--------------------------------------------------------------------------
            | Excel kamu hanya punya satu kolom utama:
            | RkAnggota
            |--------------------------------------------------------------------------
            */
            $rawDescription = $row[0] ?? null;

            if (!$rawDescription) {
                $this->skipped++;
                continue;
            }

            $description = $this->cleanDescription((string) $rawDescription);

            /*
            |--------------------------------------------------------------------------
            | Skip Header
            |--------------------------------------------------------------------------
            | Baris pertama biasanya berisi "RkAnggota".
            |--------------------------------------------------------------------------
            */
            if (
                $index === 0
                && mb_strtolower(trim($description)) === 'rkanggota'
            ) {
                $this->skipped++;
                continue;
            }

            if ($description === '') {
                $this->skipped++;
                continue;
            }

            $hash = $this->makeHash($description);

            RkAnggotaTemplate::updateOrCreate(
                [
                    'description_hash' => $hash,
                ],
                [
                    'description' => $description,
                    'category' => $this->guessCategory($description),
                    'is_active' => true,
                ]
            );

            $this->imported++;
        }
    }

    private function cleanDescription(string $value): string
    {
        $value = trim($value);

        /*
        |--------------------------------------------------------------------------
        | Rapikan line ending
        |--------------------------------------------------------------------------
        | Kalau ada cell Excel multiline, newline tetap dipertahankan.
        |--------------------------------------------------------------------------
        */
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        /*
        |--------------------------------------------------------------------------
        | Rapikan spasi berlebihan per baris
        |--------------------------------------------------------------------------
        */
        $lines = explode("\n", $value);

        $lines = array_map(function ($line) {
            return preg_replace('/[ \t]+/u', ' ', trim($line));
        }, $lines);

        return trim(implode("\n", array_filter($lines, fn ($line) => $line !== '')));
    }

    private function makeHash(string $description): string
    {
        $normalized = mb_strtolower(
            preg_replace('/\s+/u', ' ', trim($description))
        );

        return hash('sha256', $normalized);
    }

    private function guessCategory(string $description): ?string
    {
        $text = mb_strtolower($description);

        return match (true) {
            str_contains($text, 'survei') || str_contains($text, 'pendataan') || str_contains($text, 'sensus') => 'Survei/Pendataan',
            str_contains($text, 'publikasi') || str_contains($text, 'brs') || str_contains($text, 'rilis') => 'Publikasi',
            str_contains($text, 'pengolahan') || str_contains($text, 'data') || str_contains($text, 'metadata') => 'Pengolahan Data',
            str_contains($text, 'pembinaan') || str_contains($text, 'pelatihan') || str_contains($text, 'workshop') || str_contains($text, 'briefing') => 'Pembinaan/Pelatihan',
            str_contains($text, 'keuangan') || str_contains($text, 'dipa') || str_contains($text, 'ikpa') => 'Keuangan',
            str_contains($text, 'ti') || str_contains($text, 'aplikasi') || str_contains($text, 'website') => 'TI/Website',
            str_contains($text, 'humas') || str_contains($text, 'konten') || str_contains($text, 'publisitas') => 'Humas/Konten',
            str_contains($text, 'arsip') || str_contains($text, 'bmn') || str_contains($text, 'administrasi') || str_contains($text, 'kepegawaian') => 'Administrasi',
            default => 'Umum',
        };
    }
}