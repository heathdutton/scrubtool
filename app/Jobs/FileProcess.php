<?php

namespace App\Jobs;

use App\File;
use App\Imports\FileImport;
use App\Imports\FileImportAnalysis;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Maatwebsite\Excel\Facades\Excel;

class FileProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** @var int */
    const MINUTES_TILL_DELETION = 59;

    public $deleteWhenMissingModels = true;

    protected $fileId;

    public function __construct($fileId)
    {
        $this->fileId = $fileId;
    }

    public function handle()
    {
        /** @var File $file */
        $file = File::find($this->fileId);
        if ($file) {
            try {
                if ($file->status & File::STATUS_ADDED) {
                    $file->status  = File::STATUS_ANALYSIS;
                    $file->message = '';
                    $file->save();

                    $fileImportAnalysis = new FileImportAnalysis($file);
                    Excel::import(
                        $fileImportAnalysis,
                        $file->input_location,
                        null,
                        $file->type
                    );

                    $file->columns      = $fileImportAnalysis->getAnalysis()['columns'];
                    $file->column_count = count($file->columns);
                    $file->status       = File::STATUS_INPUT_NEEDED;
                    $file->message      = '';
                    $file->save();
                }

                if ($file->status & File::STATUS_READY) {
                    $file->status  = File::STATUS_RUNNING;
                    $file->message = '';
                    $file->save();

                    $fileImport = new FileImport($file);
                    Excel::import(
                        $fileImport,
                        $file->input_location,
                        null,
                        $file->type
                    );

                    Excel::store(
                        $fileImport->getExport(),
                        $file->getRelativeLocation($file->output_location),
                        null,
                        $file->type, [
                        'visibility' => File::PRIVATE_STORAGE,
                    ]);
                    $fileImport->persistStats();

                    $file->status  = File::STATUS_WHOLE;
                    $file->message = '';

                    $this->saveAndQueueForDelete($file, self::MINUTES_TILL_DELETION);
                }

            } catch (Exception $exception) {
                $file->status  = File::STATUS_STOPPED;
                $file->message = 'An error was encountered while processing your file. '.$exception->getMessage();

                $this->saveAndQueueForDelete($file, 15);
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
            ->delay($file->available_till)
            ->onQueue('delete');
    }

    /**
     * @param  Exception  $exception
     */
    public function failed(Exception $exception)
    {
        $file = File::find($this->fileId);
        if ($file) {
            $file->status  = File::STATUS_STOPPED;
            $file->message = 'An error was encountered while processing your file. '.$exception->getMessage();

            $this->saveAndQueueForDelete($file, 15);
        }
    }
}
