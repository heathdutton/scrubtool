<?php

namespace App\Jobs;

use App\File;
use App\Imports\CustomReader;
use App\Imports\FileImportSheetAnalysis;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Maatwebsite\Excel\Excel;

class FileAnalyze implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $deleteWhenMissingModels = true;

    private $fileId;

    /** @var int */
    public $timeout = 900;

    public function __construct($fileId)
    {
        $this->fileId = $fileId;
        $this->queue  = 'analyze';
    }

    public function handle()
    {
        /** @var File $file */
        $file = File::query()->findOrFail($this->fileId);
        if ($file && $file->status & File::STATUS_ADDED) {
            $tempFile      = null;
            $file->status  = File::STATUS_ANALYSIS;
            $file->message = '';
            $file->save();

            /**
             * @var Excel $excel
             * @var CustomReader $reader
             */
            list($excel, $reader) = resolve('excelCustom');

            // Take shortcuts with ultra-large plaintext files.
            $fileLocationForAnalysis = $file->input_location;
            $sheets                  = [];
            if ($file->isLargeCsv()) {
                $totalRows = 0;
                if (
                    0 !== stripos(PHP_OS, 'WIN')
                    && !in_array('shell_exec', explode(',', strtolower(ini_get('disable_functions'))))
                ) {
                    // ~50x faster wc function.
                    $result = explode(' ',
                        trim(shell_exec('wc -l '.escapeshellarg($file->input_location).' 2>/dev/null')));
                    if (count($result) > 1) {
                        $totalRows = (int) $result[0];
                    }
                }
                if (!$totalRows) {
                    $handle = fopen($file->input_location, 'r');
                    if ($handle) {
                        while (($buffer = fgets($handle, 4096)) !== false) {
                            $totalRows += substr_count($buffer, PHP_EOL);
                        }
                        fclose($handle);
                    }
                }
                if ($totalRows) {
                    $sheets = ['Worksheet' => $totalRows];
                    $reader->setTotalRows($sheets);
                }

                // Now get the first chunk of the file for analysis via  so that we don't have to load,
                // the entire file into ram.
                if ($handle = fopen($file->input_location, 'r')) {
                    $data = fread($handle, File::LARGE_FILE_CHUNK);
                    fclose($handle);
                    $tempFile = tmpfile();
                    if (fwrite($tempFile, $data)) {
                        $fileLocationForAnalysis = stream_get_meta_data($tempFile)['uri'];
                    }
                }
            }
            $import = new FileImportSheetAnalysis($file);
            $excel->import(
                $import,
                $fileLocationForAnalysis,
                null,
                $file->type
            );
            if (!$sheets) {
                $sheets = $reader->getTotalRows();
            }
            $file->columns      = $import->getAnalysis()['columns'];
            $file->column_count = count($file->columns);
            $file->status       = File::STATUS_INPUT_NEEDED;
            $file->message      = '';
            $file->rows_total   = max($file->rows_total, array_sum($sheets));
            $file->sheets       = $sheets;
            $file->save();

            if ($tempFile) {
                fclose($tempFile);
            }
        }
    }

    /**
     * @param  Exception  $exception
     */
    public function failed(Exception $exception)
    {
        report($exception);
        /** @var File $file */
        $file = File::query()->findOrFail($this->fileId);
        if ($file) {
            $file->status  = File::STATUS_STOPPED;
            $file->message = 'An error was encountered while analyzing your file. It was purged for your security. '.$exception->getMessage();

            $this->saveAndQueueForDelete($file, 1);
        }
    }

    /**
     * @param  File  $file
     * @param  int  $minTillDelete
     */
    private function saveAndQueueForDelete(File $file, $minTillDelete)
    {
        $file->available_till = Carbon::now('UTC')->addMinutes($minTillDelete);
        $file->save();

        FileDelete::dispatch($file->id)
            ->delay($file->available_till);
    }
}
