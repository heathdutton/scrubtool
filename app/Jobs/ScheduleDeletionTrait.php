<?php

namespace App\Jobs;

use App\Models\File;
use Carbon\Carbon;

trait ScheduleDeletionTrait
{

    /**
     * @param  File  $file
     * @param $minTillDelete
     */
    private function scheduleDeletion(File $file, $minTillDelete)
    {
        $file->available_till = Carbon::now('UTC')->addMinutes(max(1, (int) $minTillDelete));
        $file->save();

        FileDelete::dispatch($file->id)
            ->delay($file->available_till);
    }
}
