<?php

namespace App\Models;

class FileUpload extends ActionAbstract
{
    /**
     * @return mixed
     */
    public function file()
    {
        return $this->belongsTo(File::class)->withTrashed();
    }

}
