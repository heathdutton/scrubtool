<?php

namespace App\Models;

class FileDownload extends ActionAbstract
{
    /**
     * @return mixed
     */
    public function files()
    {
        return $this->belongsTo(File::class)->withTrashed();
    }

}
