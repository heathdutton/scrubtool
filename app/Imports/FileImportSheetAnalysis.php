<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithLimit;

class FileImportSheetAnalysis extends FileImportSheet implements WithLimit, WithChunkReading
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

    public function chunkSize(): int
    {
        return self::CHUNK_SIZE;
    }
}
