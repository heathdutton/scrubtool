<?php

namespace App\Jobs;

use App\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class FileDelete implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $deleteWhenMissingModels = true;

    private $fileId;

    public function __construct($fileId)
    {
        $this->fileId = $fileId;
    }

    public function handle()
    {
        /** @var File $file */
        $file = File::find($this->fileId);
        if ($file) {
            $file->delete();
        }
    }
}
