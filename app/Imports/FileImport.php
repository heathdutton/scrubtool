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

    /** @var int */
    const CHUNK_SIZE = 1009;

    /** @var int Time between saves of processing statistics. */
    const TIME_BETWEEN_SAVES = 1.0;

    /** @var int */
    private $rowIndex = 0;

    /** @var FileAnalysisHelper */
    private $FileAnalysisHelper;

    /** @var FileExport export */
    private $export = null;

    /** @var File */
    private $file;

    /** @var array */
    private $samples = [];

    /** @var int */
    private $timeOfStart;

    /** @var int */
    private $timeOfLastSave;

    /**
     * FileImport constructor.
     *
     * @param  File  $file
     */
    public function __construct(File $file)
    {
        $this->file           = $file;
        $this->timeOfStart    = microtime(true);
        $this->timeOfLastSave = $this->timeOfStart;
    }

    /**
     * @return FileExport
     */
    public function getExport()
    {
        return $this->export;
    }

    /**
     * @param  array  $row
     *
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Model[]|void|null
     */
    public function model(array $row)
    {
        $data = $this->getFileAnalysisHelper()
            ->parseRow($row, ++$this->rowIndex);

        if ($this->file->status & File::STATUS_ANALYSIS) {
            // Running an analysis. No export needed.
            if ($this->rowIndex <= 20 && !$data->getRowIsHeader()) {
                $this->samples[] = $data->getRowData();
            }
        }

        // Running an import/export process.
        if ($this->file->status & File::STATUS_RUNNING) {
            $this->appendRowToExport($data->getRowData());
        }
    }

    /**
     * @return FileAnalysisHelper
     */
    private function getFileAnalysisHelper()
    {
        if (!$this->FileAnalysisHelper) {
            $this->FileAnalysisHelper = new FileAnalysisHelper();
        }

        return $this->FileAnalysisHelper;
    }

    /**
     * @param $row
     */
    private function appendRowToExport($row)
    {
        if (!$this->export) {
            $this->export = new FileExport($this->file);
        }
        $this->export->appendRowToSheet($row);

        if (0 == $this->rowIndex % 20) {
            $now = microtime(true);
            if (($now - $this->timeOfLastSave) >= self::TIME_BETWEEN_SAVES) {

                // @todo - Persist stats.

                $this->timeOfLastSave = $now;
            }
        }
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return self::CHUNK_SIZE;
    }

    /**
     * @return array
     */
    public function getAnalysis()
    {
        // Get column analysis from the helper.
        $columns = $this->getFileAnalysisHelper()->getColumnAnalysis();

        // Append column examples.
        foreach ($this->samples as $rowIndex => $sample) {
            foreach ($columns as $columnIndex => &$column) {
                if (!isset($column['samples'])) {
                    $column['samples'] = [];
                }
                $column['samples'][$rowIndex] = $sample[$columnIndex] ?? null;
            }
        }

        return [
            'columns' => $columns,
        ];
    }

}
