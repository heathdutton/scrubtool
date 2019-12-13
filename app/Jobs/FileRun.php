<?php

namespace App\Jobs;

use App\Imports\CustomReader;
use App\Imports\FileImportSheet;
use App\Imports\LargeCsvReader;
use App\Models\File;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Date;
use Maatwebsite\Excel\Excel;

class FileRun implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, ScheduleDeletionTrait;

    public $deleteWhenMissingModels = true;

    /** @var int */
    public $timeout = 21600;

    private $fileId;

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
                $file->status      = File::STATUS_RUNNING;
                $file->run_started = Date::now();
                $file->message     = '';
                $file->save();

                // @todo - Complete multiple sheet support and use FileImport here. Columns array must support multiple sheets to do this.

                $input = $file->getValidatedInputLocation();

                /** @var FileImportSheet $fileImport */
                $fileImport = new FileImportSheet($file);

                [$excel, $reader] = resolve('excelCustom');
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

                $file->status        = File::STATUS_WHOLE;
                $file->message       = '';
                $file->run_completed = Date::now();

                $this->scheduleDeletion($file, config('app.file_minutes_available'));
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
        $file = File::query()->find($this->fileId);
        if ($file) {
            $file->status  = File::STATUS_STOPPED;
            $file->message = 'An error was encountered while processing your file. It was purged for your security. '.$exception->getMessage();

            $this->scheduleDeletion($file, 15);
        }
    }
}
