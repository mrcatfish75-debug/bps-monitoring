<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DailyTaskExport implements FromCollection, WithHeadings, WithMapping
{
    protected $tasks;

    public function __construct($tasks)
    {
        $this->tasks = $tasks;
    }

    public function collection()
    {
        return $this->tasks;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Pegawai',
            'Project',
            'Kegiatan Harian',
            'Status IKI',
            'Bukti (URL)',
        ];
    }

    public function map($task): array
    {
        return [
            optional($task->date)->format('Y-m-d'),

            $task->rkAnggota?->user?->name ?? '-',

            $task->rkAnggota?->project?->name ?? '-',

            $task->activity ?? '-',

            $task->iki?->status_label
                ?? ucfirst($task->iki?->status ?? '-'),

            $task->evidence_url ?? '-',
        ];
    }
}