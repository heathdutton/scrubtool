<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithLimit;

class FileImportAnalysis extends FileImport implements WithLimit, WithChunkReading
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
