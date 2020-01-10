<?php

namespace App\Models;

class SuppressionListSupportScrub extends ActionAbstract
{
    /**
     * @return mixed
     */
    public function file()
    {
        return $this->belongsTo(File::class)->withTrashed();
    }

    /**
     * @return mixed
     */
    public function suppressionListSupport()
    {
        return $this->belongsTo(SuppressionListSupport::class)->withTrashed();
    }
}
