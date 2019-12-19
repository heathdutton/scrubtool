<?php

namespace App\Imports;

use App\Exports\FileExport;
use App\Helpers\FileAnalysisHelper;
use App\Helpers\FileHashHelper;
use App\Helpers\FileSuppressionListHelper;
use App\Models\File;
use App\Models\SuppressionList;
use App\Notifications\HashFileReadyNotification;
use App\Notifications\ScrubFileReadyNotification;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToModel;

class FileImportSheet implements ToModel
{
    use SkipsFailures, SkipsErrors;

    /** @var int Time between saves of processing statistics. */
    const TIME_BETWEEN_SAVES = 1.0;

    /** @var FileSuppressionListHelper */
    protected $fileSuppressionListHelper;

    /** @var array */
    private $stats;

    /** @var int */
    private $rowIndex = 0;

    /** @var FileAnalysisHelper */
    private $fileAnalysisHelper;

    /** @var FileHashHelper */
    private $fileHashHelper;

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

        // Resume previous statistics.
        foreach (File::STATS_DEFAULT as $stat => $default) {
            $this->stats[$stat] = $file->{$stat} ?? $default;
        }
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
     * @return Model|Model[]|void|null
     * @throws Exception
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
                    $this->stats['rows_filled']++;

                    if ($this->file->mode & File::MODE_SCRUB) {
                        if ($this->getFileSuppressionListHelper()->scrubRow($row)) {
                            $this->stats['rows_scrubbed']++;
                        }
                    }

                    if ($row && ($this->file->mode & (File::MODE_LIST_CREATE | File::MODE_LIST_APPEND | File::MODE_LIST_REPLACE))) {
                        if ($this->getFileSuppressionListHelper()->appendRowToList($row, $this->rowIndex)) {
                            $this->stats['rows_imported']++;
                        } else {
                            $this->stats['rows_invalid']++;
                        }
                    }

                    if ($row && ($this->file->mode & File::MODE_HASH)) {
                        if ($this->getFileHashHelper()->modifyRowForOutput($row)) {
                            $this->stats['rows_hashed']++;
                        } else {
                            $this->stats['rows_invalid']++;
                        }
                    }

                    if ($row && ($this->file->mode & (File::MODE_HASH | File::MODE_SCRUB))) {
                        $this->appendRowToExport($row);
                    }
                }
            }
        }

        if (0 == $this->rowIndex % 20) {
            $now = microtime(true);
            if (($now - $this->timeOfLastSave) >= self::TIME_BETWEEN_SAVES) {
                $this->persistStats();
            }
        }

        if ($row && $this->rowIndex <= 10 && !$analysis->getRowIsHeader()) {
            $this->samples[] = $row;
        }

        $this->stats['rows_processed']++;

        return null;
    }

    /**
     * @return FileAnalysisHelper
     */
    private function getFileAnalysisHelper()
    {
        if (!$this->fileAnalysisHelper) {
            $this->fileAnalysisHelper = new FileAnalysisHelper($this->file);
        }

        return $this->fileAnalysisHelper;
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
     * @return FileSuppressionListHelper
     * @throws Exception
     */
    private function getFileSuppressionListHelper()
    {
        if (!$this->fileSuppressionListHelper) {
            $this->fileSuppressionListHelper = new FileSuppressionListHelper($this->file);
        }

        return $this->fileSuppressionListHelper;
    }

    /**
     * @return FileHashHelper
     */
    private function getFileHashHelper()
    {
        if (!$this->fileHashHelper) {
            $this->fileHashHelper = new FileHashHelper($this->file);
        }

        return $this->fileHashHelper;
    }

    /**
     * @return $this
     */
    private function persistStats()
    {
        foreach ($this->stats as $stat => $value) {
            if ($value) {
                $this->file->setAttribute($stat, $value);
            }
        }
        $this->timeOfLastSave = microtime(true);

        $this->file->save();

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function finish()
    {
        if (
            $this->fileSuppressionListHelper
            && $this->file
            && $this->file->mode & (File::MODE_LIST_CREATE | File::MODE_LIST_APPEND | File::MODE_LIST_REPLACE)
        ) {
            // Finish saving changes to the suppression list and it's supports.
            // Notifications will be fired based on the persisted suppression list data.
            $this->stats['rows_persisted'] = $this->fileSuppressionListHelper->finish();
        }
        $this->stats['rows_processed'] = max($this->stats['rows_processed'], $this->stats['rows_total']);
        if ($this->file->mode & File::MODE_HASH) {
            // Notify the user of a file ready to download.
            $notification = new HashFileReadyNotification($this->file);
            if ($this->file->user) {
                // Notify the user.
                $this->file->user->notify($notification);
            } else {
                // Notify the owner of the file if possible.
                $this->file->notify($notification);
            }
        }
        if ($this->file->mode & File::MODE_SCRUB) {
            // Notify the user of a file ready to download.
            $notification = new ScrubFileReadyNotification($this->file);
            if ($this->file->user) {
                // Notify the user.
                $this->file->user->notify($notification);
            } else {
                // Notify the owner of the file if possible.
                $this->file->notify($notification);
            }
        }

        $this->persistStats();

        return $this;
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
