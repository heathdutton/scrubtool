<?php

namespace App\Jobs;

use App\File;
use App\Imports\CustomReader;
use App\Imports\FileImportSheet;
use App\Imports\LargeCsvReader;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Maatwebsite\Excel\Excel;

class FileRun implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** @var int */
    const MINUTES_TILL_DELETION = 59;

    public $deleteWhenMissingModels = true;

    private $fileId;

    /** @var int */
    public $timeout = 21600;

    public function __construct($fileId)
    {
        $this->fileId = $fileId;
        $this->queue  = 'run';
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function handle()
    {
        /** @var File $file */
        $file = File::query()->findOrFail($this->fileId);
        if ($file) {
            if ($file->status & File::STATUS_READY) {
                $file->status  = File::STATUS_RUNNING;
                $file->message = '';
                $file->save();

                // @todo - Complete multiple sheet support and use FileImport here. Columns array must support multiple sheets to do this.

                $input = $file->getValidatedInputLocation();

                /** @var FileImportSheet $fileImport */
                $fileImport = new FileImportSheet($file);

                list($excel, $reader) = resolve('excelCustom');
                $reader->setTotalRows($file->sheets);

                if ($file->isLargeCsv()) {
                    /**
                     * Perform a streamlined import to save time/memory.
                     */
                    $largeCsvReader = new LargeCsvReader();
                    $largeCsvReader->setDelimiter(config('excel.imports.csv.delimiter'));
                    $largeCsvReader->setEnclosure(config('excel.imports.csv.enclosure'));
                    $largeCsvReader->setEscapeCharacter(config('excel.imports.csv.escape_character'));
                    $largeCsvReader->setContiguous(config('excel.imports.csv.contiguous')); // Currently ignored.
                    $largeCsvReader->setInputEncoding(config('excel.imports.csv.input_encoding'));
                    $largeCsvReader->loadIntoCallback($input,
                        function ($row, $rowIndex) use ($fileImport) {
                            $fileImport->model($row);
                        });
                } else {
                    /**
                     * @var Excel $excel
                     * @var CustomReader $reader
                     */
                    $excel->import(
                        $fileImport,
                        $input,
                        null,
                        $file->type
                    );
                }
                $excel->store(
                    $fileImport->getExport(),
                    $file->getRelativeLocation($file->output_location),
                    null,
                    $file->type, [
                    'visibility' => File::PRIVATE_STORAGE,
                ]);

                $fileImport->finish();

                $file->status  = File::STATUS_WHOLE;
                $file->message = '';

                $this->saveAndQueueForDelete($file, self::MINUTES_TILL_DELETION);
            }
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

    /**
     * @param  Exception  $exception
     */
    public function failed(Exception $exception)
    {
        report($exception);
        /** @var File $file */
        $file = File::query()->find($this->fileId);
        if ($file) {
            $file->status  = File::STATUS_STOPPED;
            $file->message = 'An error was encountered while processing your file. It was purged for your security. '.$exception->getMessage();

            $this->saveAndQueueForDelete($file, 15);
        }
    }
}
