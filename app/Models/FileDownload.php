<?php

namespace App\Models;

class FileDownload extends ActionAbstract
{
    /**
     * @return mixed
     */
    public function file()
    {
        return $this->belongsTo(File::class)->withTrashed();
    }

}
