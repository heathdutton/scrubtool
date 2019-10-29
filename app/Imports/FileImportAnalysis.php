<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithLimit;

class FileImportAnalysis extends FileImport implements WithLimit
{
    /**
     * @return int
     */
    public function limit(): int
    {
        return 101;
    }
}
