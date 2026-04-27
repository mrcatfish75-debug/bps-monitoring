<?php

namespace App\Imports;

use App\Models\Iku;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class IkuImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (
            empty($row['name']) ||
            empty($row['year']) ||
            empty($row['satuan']) ||
            empty($row['target'])
        ) {
            return null;
        }

        return new Iku([
            'name' => $row['name'],
            'year' => $row['year'],
            'satuan' => $row['satuan'],
            'target' => $row['target'],
        ]);
    }
}