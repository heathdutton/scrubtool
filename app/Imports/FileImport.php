<?php

namespace App\Imports;

use App\Exports\FileExport;
use App\File;
use App\Helpers\FileAnalysisHelper;
use App\Helpers\FileHashHelper;
use App\SuppressionList;
use Exception;
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
        } elseif ($this->file->status & File::STATUS_RUNNING) {

            if ($analysis->rowIsValid()) {
                if (!$analysis->getRowIsHeader()) {
                    $this->stats['rows_total']++;

                    if ($this->file->mode & File::MODE_HASH) {
                        if ($this->getFileHashHelper()->hashRow($row)) {
                            $this->stats['rows_hashed']++;
                        }
                        $this->appendRowToExport($row);
                    }

                    if ($this->file->mode & File::MODE_LIST_CREATE) {
                        $this->appendRowToList($row);
                    }
                }
            } else {
                $this->stats['rows_invalid']++;
            }

            $this->stats['rows_processed']++;
            $this->checkStats();
        }

        if ($this->rowIndex <= 20 && !$analysis->getRowIsHeader()) {
            $this->samples[] = $row;
        }
    }

    /**
     * @return FileAnalysisHelper
     */
    private function getFileAnalysisHelper()
    {
        if (!$this->FileAnalysisHelper) {
            $this->FileAnalysisHelper = new FileAnalysisHelper($this->file->country ?? 'US');
        }

        return $this->FileAnalysisHelper;
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
     * @param $row
     */
    private function appendRowToExport($row)
    {
        if (!$this->export) {
            $this->export = new FileExport($this->file);
        }
        $this->export->appendRowToSheet($row);
    }

    /**
     * @param $row
     *
     * @throws Exception
     */
    private function appendRowToList($row)
    {
        if (!$this->list) {
            if ($this->file->mode & File::MODE_LIST_CREATE) {
                if (!$this->file->user_id) {
                    throw new Exception(__('The file must be associated with a logged in user in order to be associated with a suppression list. Please log in and try again.'));
                }
                $this->list          = new SuppressionList();
                $this->list->user_id = $this->file->user_id;

                // @todo - Making a suppression list global is an admin feature only, but could exist in this UI.
                $this->list->global = 0;
                $this->list->mode   = 0;

                // Suppression lists can contain email/phone/both at the moment.
                $emailColumns = $this->file->getHashableColumnIds(FileAnalysisHelper::TYPE_EMAIL);
                if ($emailColumns) {
                    $this->has_plain_text = true;
                    $this->list->mode += SuppressionList::MODE_DO_NOT_EMAIL;
                }
                $phoneColumns = $this->file->getHashableColumnIds(FileAnalysisHelper::TYPE_PHONE);
                if ($phoneColumns) {
                    $this->has_plain_text = true;
                    $this->list->mode += SuppressionList::MODE_DO_NOT_PHONE;
                }

                if (!$this->list->mode) {
                    throw new Exception(__('There was no Email or Phone column to build your suppression list from. Please make sure you indicate the file contents and try again.'));
                }

                $this->list->save();
                $this->list->files()->attach($this->file->id);
            }
            if ($this->file->mode & File::MODE_LIST_APPEND || $this->file->mode & File::MODE_LIST_REPLACE) {
                // @todo - Load and confirm existing list.
                throw new Exception(__('List append function does not yet exist.'));
            }
            if ($this->file->mode & File::MODE_LIST_REPLACE) {
                // @todo - Drop all tables associated with the list to start fresh.
                throw new Exception(__('List replace function does not yet exist.'));
            }
        }
        if ($this->list) {
            $this->list->appendRowToList($row);
        }
    }

    private function checkStats()
    {
        if (0 == $this->rowIndex % 20) {
            $now = microtime(true);
            if (($now - $this->timeOfLastSave) >= self::TIME_BETWEEN_SAVES) {
                $this->persistStats();
            }
        }
    }

    /**
     * @return bool
     */
    public function persistStats()
    {
        foreach ($this->stats as $stat => $value) {
            $this->file->setAttribute($stat, $value);
        }
        $this->timeOfLastSave = microtime(true);

        return $this->file->save();
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
