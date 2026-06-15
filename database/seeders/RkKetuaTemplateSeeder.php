<?php

namespace Database\Seeders;

use App\Imports\RkKetuaTemplateImport;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class RkKetuaTemplateSeeder extends Seeder
{
    public function run(): void
    {
        Excel::import(
            new RkKetuaTemplateImport,
            storage_path('app/imports/rk-ketua.xlsx')
        );
    }
}