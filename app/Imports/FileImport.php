<?php

namespace App\Imports;

use App\Exports\FileExport;
use App\File;
use App\FileSuppressionList;
use App\Helpers\FileAnalysisHelper;
use App\Helpers\FileHashHelper;
use App\SuppressionList;
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
        'rows_imported'        => 0,
        'rows_scrubbed'        => 0,
        'rows_hashed'          => 0,
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

    /** @var FileSuppressionList */
    protected $FileSuppressionList;

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

    /** @var SuppressionList */
    private $list;

    /** @var array */
    private $columnsFilled = [];

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
     * @throws \Exception
     */
    public function model(array $row)
    {
        $analysis = $this->getFileAnalysisHelper()
            ->parseRow($row, ++$this->rowIndex);

        if ($this->file->status & File::STATUS_ANALYSIS) {
            // Running an analysis. No export needed.
            if ($analysis->rowIsValid() && !$analysis->getRowIsHeader()) {
                $this->setColumnsFilled($row);
            }
        } elseif ($this->file->status & File::STATUS_RUNNING) {
            if ($analysis->rowIsValid()) {
                if ($analysis->getRowIsHeader()) {
                    if ($this->file->mode & (File::MODE_HASH | File::MODE_SCRUB)) {
                        $this->appendRowToExport($row);
                    }
                } else {
                    $this->stats['rows_total']++;

                    if ($this->file->mode & File::MODE_SCRUB) {

                        if ($this->getFileSuppressionList()->scrubRow($row)) {
                            $this->stats['rows_scrubbed']++;
                        }
                    }

                    if ($row && $this->file->mode & File::MODE_LIST_CREATE) {
                        if ($this->getFileSuppressionList()->appendRowToList($row, $this->rowIndex)) {
                            $this->stats['rows_imported']++;
                        } else {
                            $this->stats['rows_invalid']++;
                        }
                    }

                    if ($row && $this->file->mode & File::MODE_HASH) {
                        if ($this->getFileHashHelper()->modifyRowForOutput($row)) {
                            $this->appendRowToExport($row);
                            $this->stats['rows_hashed']++;
                        } else {
                            $this->stats['rows_invalid']++;
                        }
                    }

                }
            } else {
                $this->stats['rows_invalid']++;
            }

            if (0 == $this->rowIndex % 20) {
                $now = microtime(true);
                if (($now - $this->timeOfLastSave) >= self::TIME_BETWEEN_SAVES) {
                    $this->persistStats();
                }
            }
        }

        if ($row && $this->rowIndex <= 20 && !$analysis->getRowIsHeader()) {
            $this->samples[] = $row;
        }
    }

    /**
     * @return FileAnalysisHelper
     */
    private function getFileAnalysisHelper()
    {
        if (!$this->FileAnalysisHelper) {
            $this->FileAnalysisHelper = new FileAnalysisHelper($this->file);
        }

        return $this->FileAnalysisHelper;
    }

    /**
     * @param $row
     */
    private function setColumnsFilled($row)
    {
        foreach ($row as $columnIndex => $value) {
            if (!isset($this->columnsFilled[$columnIndex]) && !empty($value)) {
                $this->columnsFilled[$columnIndex] = true;
            }
        }
    }

    /**
     * @param $row
     *
     * @return $this
     */
    private function appendRowToExport($row)
    {
        if (!$this->export) {
            $this->export = new FileExport($this->file);
        }
        $this->export->appendRowToSheet($row);

        return $this;
    }

    /**
     * @return FileSuppressionList
     * @throws \Exception
     */
    private function getFileSuppressionList()
    {
        if (!$this->FileSuppressionList) {
            $this->FileSuppressionList = new FileSuppressionList([], $this->file);
        }

        return $this->FileSuppressionList;
    }

    /**
     * @return FileHashHelper
     */
    private function getFileHashHelper()
    {
        if (!$this->FileHashHelper) {
            $this->FileHashHelper = new FileHashHelper($this->file);
        }

        return $this->FileHashHelper;
    }

    /**
     * @return $this
     */
    private function persistStats()
    {
        foreach ($this->stats as $stat => $value) {
            $this->file->setAttribute($stat, $value);
        }
        $this->timeOfLastSave = microtime(true);

        $this->file->save();

        return $this;
    }

    /**
     * @return $this
     */
    public function finish()
    {
        if ($this->FileSuppressionList) {
            $this->FileSuppressionList->finish();
        }
        $this->persistStats();

        return $this;
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

        // Add samples collected after parsing.
        foreach ($this->samples as $rowIndex => $sample) {
            foreach ($columns as $columnIndex => $column) {
                if (!isset($columns[$columnIndex]['samples'])) {
                    $columns[$columnIndex]['samples'] = [];
                }
                $columns[$columnIndex]['samples'][$rowIndex] = $sample[$columnIndex] ?? null;
            }
        }

        // Add boolean to indicate that the
        foreach ($columns as $columnIndex => $column) {
            $columns[$columnIndex]['filled'] = $this->columnsFilled[$columnIndex] ?? false;
        }

        return [
            'columns' => $columns,
        ];
    }

}
