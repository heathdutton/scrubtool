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
    const MINUTES_TILL_DELETION = 90;

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

                    $file->status  = File::STATUS_WHOLE;
                    $file->message = '';
                    $file->save();

                    $this->queueForDeletion($file, self::MINUTES_TILL_DELETION);
                }

            } catch (Exception $exception) {
                $file->status  = File::STATUS_STOPPED;
                $file->message = 'An error was encountered while processing your file. '.$exception->getMessage();
                $file->save();

                $this->queueForDeletion($file, 1);
            }
        }
    }

    /**
     * @param  File  $file
     * @param $delayMinutes
     */
    private function queueForDeletion(File $file, $delayMinutes)
    {
        FileDelete::dispatch($file->id)
            ->delay(Carbon::now()->addMinutes($delayMinutes))
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
            $file->save();

            $this->queueForDeletion($file, 1);
        }
    }
}
