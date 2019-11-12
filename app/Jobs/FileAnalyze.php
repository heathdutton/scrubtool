<?php

namespace App\Jobs;

use App\File;
use App\Imports\FileImportAnalysis;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Maatwebsite\Excel\Facades\Excel;

class FileAnalyze implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $deleteWhenMissingModels = true;

    private $fileId;

    public function __construct($fileId)
    {
        $this->fileId = $fileId;
        $this->queue = 'analyze';
    }

    public function handle()
    {
        /** @var File $file */
        $file = File::query()->findOrFail($this->fileId);
        if ($file && $file->status & File::STATUS_ADDED) {

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
