<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithLimit;

class FileImportSheetAnalysis extends FileImportSheet implements WithLimit
{
    /**
     * @return int
     */
    public function limit(): int
    {
        return 101;
    }
}
