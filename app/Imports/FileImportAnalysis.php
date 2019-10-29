<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithLimit;

class FileImportAnalysis extends FileImport implements WithLimit
{
    public function limit(): int
    {
        return 101;
    }
}
