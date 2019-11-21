<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class FileSuppressionList extends Pivot
{
    use SoftDeletes;

    /** @var int Indicates a file that was used to CREATE the list. */
    const REL_FILE_TO_LIST = 2;

    /** @var int Indicates a file that was SCRUBBED by the list. */
    const REL_LIST_USED_TO_SCRUB = 1;

    /** @var array */
    protected $guarded = [
        'id',
    ];
}
