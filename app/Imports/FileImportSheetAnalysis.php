<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithLimit;

class FileImportSheetAnalysis extends FileImportSheet implements WithLimit, WithChunkReading
{
    /**
     * @return int
     */
    public function limit(): int
    {
        return 101;
    }

    public function chunkSize(): int
    {
        return 101;
    }
}
