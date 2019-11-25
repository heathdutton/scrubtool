<?php

namespace App\Jobs;

use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class FileGetChecksums implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    const CHECKSUMS = ['md5', 'crc32b'];

    public $deleteWhenMissingModels = true;

    /** @var int */
    public $timeout = 600;

    private $fileId;

    public function __construct($fileId)
    {
        $this->fileId = $fileId;
        $this->queue  = 'analyze';
    }

    public function handle()
    {
        /** @var File $file */
        $file = File::query()->findOrFail($this->fileId);
        if ($file) {
            foreach (self::CHECKSUMS as $algo) {
                if (empty($file->{$algo})) {
                    $input = $file->getValidatedInputLocation();
                    if ($input) {
                        $hash = hash_file($algo, $input);
                        if ($hash) {
                            $file          = File::query()->findOrFail($this->fileId);
                            $file->{$algo} = $hash;
                            $file->save();
                        }
                    }
                }
            }
        }
    }
}
