<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithLimit;

class FileImportSheetAnalysis extends FileImportSheet implements WithLimit
{

    /** @var int */
    const CHUNK_SIZE = 101;

    /**
     * @return int
     */
    public function limit(): int
    {
        return self::CHUNK_SIZE;
    }
}
