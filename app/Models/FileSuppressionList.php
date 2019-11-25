<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class FileSuppressionList extends Pivot
{
    use SoftDeletes;

    /** @var int Indicates a file that was used to CREATE the list. */
    const REL_FILE_INTO_LIST = 2;

    /** @var int Indicates a file was used to REPLACE the list. */
    const REL_FILE_REPLACE_LIST = 4;

    /** @var int Indicates a file that was SCRUBBED by the list. */
    const REL_LIST_USED_TO_SCRUB = 1;

    /** @var array */
    protected $guarded = [
        'id',
    ];
}
