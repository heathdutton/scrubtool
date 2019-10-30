<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SuppressionList
 *
 * @package App
 */
class SuppressionList extends Model
{
    /** @var array */
    protected $guarded = [
        'id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
