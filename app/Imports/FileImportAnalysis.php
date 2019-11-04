<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithLimit;

class FileImportAnalysis extends FileImport implements WithLimit
{

    /** @var int */
    const CHUNK_SIZE = 20;

    /**
     * @return int
     */
    public function limit(): int
    {
        return 20;
    }
}
