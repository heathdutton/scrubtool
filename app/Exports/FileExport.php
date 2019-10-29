<?php

namespace App\Exports;

use App\File;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FileExport implements WithMultipleSheets
{
    use Exportable;

    /** @var array */
    protected $sheets = [];

    protected $sheetId = 0;

    /** @var File */
    private $file;

    /**
     * FileExport constructor.
     *
     * @param  File  $file  The original input file which contains settings and the basis for this export.
     */
    public function __construct(File $file)
    {
        $this->file = $file;
        $this->addSheet();
    }

    public function addSheet()
    {
        $this->sheetId++;
        // @todo - Use the file type to define the export sheet
        $this->sheets[$this->sheetId] = new FileExportSheet();
    }

    public function sheets(): array
    {
        return $this->sheets;
    }

    public function appendRowToSheet($row)
    {
        return $this->sheet()->appendRow($row);
    }

    /**
     * @return FileExportSheet
     */
    private function sheet()
    {
        return $this->sheets[$this->sheetId];
    }

    public function appendRowsToSheet($rows)
    {
        return $this->sheet()->appendRows($rows);
    }
}
