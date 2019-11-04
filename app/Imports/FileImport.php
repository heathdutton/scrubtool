<?php

namespace App\Imports;

use App\Exports\FileExport;
use App\File;
use App\Helpers\FileAnalysisHelper;
use App\Helpers\FileHashHelper;
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

    protected $stats = [
        'rows_total'           => 0,
        'rows_processed'       => 0,
        'rows_scrubbed'        => 0,
        'rows_invalid'         => 0,
        'rows_email_valid'     => 0,
        'rows_email_invalid'   => 0,
        'rows_email_duplicate' => 0,
        'rows_email_dnc'       => 0,
        'rows_phone_valid'     => 0,
        'rows_phone_invalid'   => 0,
        'rows_phone_duplicate' => 0,
        'rows_phone_dnc'       => 0,
    ];

    /** @var int */
    private $rowIndex = 0;

    /** @var FileAnalysisHelper */
    private $FileAnalysisHelper;

    /** @var FileHashHelper */
    private $FileHashHelper;

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
        $analysis = $this->getFileAnalysisHelper()
            ->parseRow($row, ++$this->rowIndex);

        if ($this->file->status & File::STATUS_ANALYSIS) {

            // Running an analysis. No export needed.
            if ($this->rowIndex <= 20 && !$analysis->getRowIsHeader()) {
                $this->samples[] = $row;
            }

        } elseif ($this->file->status & File::STATUS_RUNNING) {

            if ($analysis->rowIsValid()) {
                if ($this->file->mode & File::MODE_HASH) {
                    $this->getFileHashHelper()->hashRow($row);
                }

                $this->appendRowToExport($row);
            } else {
                $this->stats['rows_invalid']++;
            }

            $this->stats['rows_total']++;
            $this->stats['rows_processed']++;
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
     * @return FileHashHelper
     */
    private function getFileHashHelper()
    {
        if (!$this->FileHashHelper) {
            $this->FileHashHelper = new FileHashHelper($this->file->input_settings);
        }

        return $this->FileHashHelper;
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

                $this->file->rows_processed = $this->rowIndex;

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
