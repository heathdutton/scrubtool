<?php

namespace App\Jobs;

use App\File;
use App\Imports\FileImport;
use App\Imports\FileImportAnalysis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Maatwebsite\Excel\Facades\Excel;

class FileProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $deleteWhenMissingModels = true;

    protected $fileId;

    public function __construct($fileId)
    {
        $this->fileId = $fileId;
    }

    public function handle(File $file)
    {
        /** @var File $file */
        $file = $file->find($this->fileId);
        if ($file) {
            try {
                if ($file->status & File::STATUS_ADDED) {
                    $file->status  = File::STATUS_ANALYSIS;
                    $file->message = '';
                    $file->save();

                    $fileImportAnalysis = new FileImportAnalysis($file);
                    Excel::import($fileImportAnalysis, $file->input_location, null, $file->type);

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
                    Excel::import($fileImport, $file->input_location, null, $file->type);

                    Excel::store($fileImport->getExport(), $file->getRelativeOutputLocation(), null, $file->type, [
                        'visibility' => File::PRIVATE_STORAGE,
                    ]);

                    $file->status  = File::STATUS_WHOLE;
                    $file->message = '';
                    $file->save();
                }

            } catch (Exception $e) {
                $file->status  = File::STATUS_STOPPED;
                $file->message = 'An error was encountered while processing your file. '.$e->getMessage();
                $file->save();
            }
        } else {
            $file->status  = File::STATUS_STOPPED;
            $file->message = 'An error was encountered while processing your file.';
            $file->save();
        }
    }

    public function failed(Exception $exception)
    {
       // @todo - Send user notification of failure, etc..
        $tmp = 1;
        // FileDelete::dispatch()
    }
}
