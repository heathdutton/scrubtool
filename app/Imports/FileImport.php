<?php

namespace App\Imports;

use App\File;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FileImport implements WithMultipleSheets
{
    /** @var array */
    private $sheetCounts;

    /** @var bool */
    private $analysis;

    /**
     * FileImport constructor.
     *
     * @param  File  $file
     * @param  array  $sheetCounts
     * @param  bool  $analysis
     */
    public function __construct(File $file, $sheetCounts = [], $analysis = false)
    {
        $this->file        = $file;
        $this->sheetCounts = $sheetCounts;
        $this->analysis    = $analysis;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];
        foreach ($this->sheetCounts as $sheetName => $sheetRowCount) {
            if ($this->analysis) {
                $sheets[$sheetName] = new FileImportSheetAnalysis($this->file);
                break;
            }
            $sheets[$sheetName] = new FileImportSheet($this->file);
        }

        return $sheets;
    }

}
