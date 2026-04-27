<?php

namespace App\Exports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromCollection;

class ProjectExport implements FromCollection
{
    public function collection()
    {
        return Project::with(['team', 'leader', 'rkKetua'])
            ->get()
            ->map(function ($p) {
                return [
                    'Nama Project' => $p->name,
                    'Tim' => $p->team->name ?? '-',
                    'Ketua' => $p->leader->name ?? '-',
                    'IKU' => $p->rkKetua->iku->name ?? '-',
                    'Progress (%)' => $p->progress,
                    'Status' => $p->status,
                ];
            });
    }
}