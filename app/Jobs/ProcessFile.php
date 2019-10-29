<?php

namespace App\Jobs;

use App\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ProcessFile implements ShouldQueue
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
            $file->process();
        }
    }

    // public function failed(Exception $exception)
    // {
    //    @todo - Send user notification of failure, etc..
    // }
}
