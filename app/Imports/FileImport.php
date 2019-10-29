<?php

namespace App\Imports;

use App\Exports\FileExport;
use App\File;
use App\Helpers\FileAnalysisHelper;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class FileImport implements ToModel, WithChunkReading
{
    use SkipsFailures, SkipsErrors;

    const CHUNK_SIZE = 1009;

    private $rowIndex = 0;

    private $fileDataHelper;

    /** @var FileExport export */
    private $export = null;

    /** @var File */
    private $file;

    private $samples = [];

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function model(array $row)
    {
        $data = $this->getFileDataHelper()
            ->parseRow($row, ++$this->rowIndex);

        if ($this->file->status % File::STATUS_ANALYSIS) {
            // Running an analysis. No export needed.
            if (!$data->getRowIsHeader()) {
                $this->samples[] = $data->getRowData();
            }
        }

        // Running an import/export process.
        if ($this->file->status % File::STATUS_RUNNING) {
            $this->appendRowToExport($data->getRowData());
        }
    }

    /**
     * @return FileAnalysisHelper
     */
    private function getFileDataHelper()
    {
        if (!$this->fileDataHelper) {
            $this->fileDataHelper = new FileAnalysisHelper();
        }

        return $this->fileDataHelper;
    }

    private function appendRowToExport($row)
    {
        if (!$this->export) {
            $this->export = new FileExport($this->file);
        }
        $this->export->appendRowToSheet($row);
    }

    public function getAnalysis()
    {
        $columns = $this->getFileDataHelper()->getColumnAnalysis();

        // Append column examples.
        foreach ($this->samples as $rowIndex => $sample) {
            foreach ($columns as $columnIndex => &$column) {
                // if (!isset($column['samples'])) {
                //     $column['samples'] = [];
                // }
                $column['samples'][$rowIndex] = $sample[$columnIndex] ?? null;
            }
        }
        return $columns;
    }

    public function chunkSize(): int
    {
        return self::CHUNK_SIZE;
    }

    public function finish()
    {
        $this->export->store($this->fileOutput, File::STORAGE);
    }

    private function rowIsHeader($row)
    {
        foreach ($row as $value) {
            $simple = strtolower(preg_replace('/[^a-z]/i', '', $value));
            if (in_array($simple, $this->headerIdentifiers)) {
                return true;
            }
        }

        return false;
    }
}
