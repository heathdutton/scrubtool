<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class FileExportSheet implements FromArray, ShouldAutoSize, WithStrictNullComparison
{
    use Exportable;

    protected $rows = [];

    /**
     * @return array
     */
    public function array(): array
    {
        return $this->rows;
    }

    public function appendRow($row)
    {
        $this->rows[] = $row;
    }

    public function appendRows($rows = [])
    {
        array_push($this->rows, $rows);
    }
}
