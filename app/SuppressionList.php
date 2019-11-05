<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SuppressionList
 *
 * @package App
 */
class SuppressionList extends Model
{
    use SoftDeletes;

    const MODE_DO_NOT_EMAIL = 1;

    const MODE_DO_NOT_PHONE = 2;

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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function files()
    {
        return $this->belongsToMany(File::class)
            ->using(FileSuppressionList::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function supports()
    {
        return $this->hasMany(SuppressionListSupport::class);
    }
}
