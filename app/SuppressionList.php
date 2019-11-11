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
            $attributes['user_id'] = $this->file->user_id ?? null;
            $attributes['name']    = $attributes['name'] ?? $this->choseListNameFromFileName();
            $attributes['token']   = $attributes['token'] ?? $this->generateToken();
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
     * @return string
     */
    private function generateToken()
    {
        $bin    = hash('crc32', implode(',', $this->getAttributes()), true);
        $dec    = bindec($bin);
        $base35 = base_convert($dec, 10, 35);

        return $base35;
    }

    /**
     * @return string|null
     */
    public function getIdToken()
    {
        if (empty($this->id) || empty($this->token)) {
            return null;
        }
        $idString = base_convert($this->id, 10, 35);

        return $idString.'z'.$this->token;
    }

    /**
     * @param $string
     *
     * @return \Illuminate\Database\Eloquent\Builder|Model|\Illuminate\Database\Query\Builder|object|static|null
     */
    public function findByIdToken($string)
    {
        $string = strtolower(trim($string));
        list($idString, $tokenString) = explode('z', $string);
        if (!$idString || !$tokenString) {
            return null;
        }

        $id = (int) base_convert($idString, 35, 10);
        if (!$id) {
            return null;
        }

        return self::withoutTrashed()
            ->where('id', $id)
            ->where('token', $tokenString)
            ->first();
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
