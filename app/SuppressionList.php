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

    /** @var array */
    protected $guarded = [
        'id',
    ];

    /** @var File */
    private $file;

    /**
     * SuppressionList constructor.
     *
     * @param  array  $attributes
     * @param  File|null  $file
     */
    public function __construct($attributes = [], File $file = null)
    {
        $this->file = $file;

        if (!empty($this->file->id)) {
            $attributes['user_id']     = $this->file->user_id ?? null;
            $attributes['name']        = $attributes['name'] ?? $this->choseListNameFromFileName();
            $attributes['description'] = $attributes['description'] ?? '';
            $attributes['global']      = $attributes['global'] ?? 0;
            $attributes['required']    = $attributes['required'] ?? 0;
        }

        parent::__construct($attributes);
    }

    /**
     * @return string|string[]|null
     */
    private function choseListNameFromFileName()
    {
        $fileName = trim($this->file->name);
        $fileName = substr($fileName, 0, strrpos($fileName, '.'));
        $fileName = preg_replace('/[^a-z0-9\-\.]/i', ' ', $fileName);
        $fileName = preg_replace('/\s+/', ' ', $fileName);
        $fileName = ucwords($fileName);
        if (empty($fileName)) {
            $fileName = __('Untitled');
        }

        return $fileName;
    }

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
