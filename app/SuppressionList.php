<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;

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

    /** @var array */
    private $statsChildren = [];

    /** @var array */
    private $statsParent = [];

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
            $attributes['token']       = $attributes['token'] ?? $this->generateToken($attributes);
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
     * @param $attributes
     *
     * @return string
     */
    private function generateToken($attributes)
    {
        $hash   = hash('crc32', uniqid(implode(chr(31), $attributes)), false);
        $base35 = base_convert($hash, 16, 35);

        return $base35;
    }

    /**
     * @param  string  $stat
     *
     * @return int
     */
    public function statChild($stat)
    {
        $this->getStatsChildren();

        return $this->statsChildren[$stat] ?? 0;
    }

    /**
     * @return array
     */
    private function getStatsChildren()
    {
        if (!$this->statsChildren) {
            $this->statsChildren = File::STATS_DEFAULT;
            /** @var File $file */
            foreach (self::files()->where('relationship', FileSuppressionList::RELATIONSHIP_CHILD)->get() as $file) {
                foreach ($this->statsChildren as $key => &$value) {
                    if (!empty($file->{$key})) {
                        $value += $file->{$key};
                    }
                }
            }
        }

        return $this->statsChildren;
    }

    /**
     * @return BelongsToMany
     */
    public function files()
    {
        return $this->belongsToMany(File::class)
            ->using(FileSuppressionList::class);
    }

    /**
     * @param  string  $stat
     *
     * @return int
     */
    public function statParent($stat)
    {
        $this->getStatsParent();

        return $this->statsParent[$stat] ?? 0;
    }

    /**
     * @return array
     */
    private function getStatsParent()
    {
        if (!$this->statsParent) {
            $this->statsParent = File::STATS_DEFAULT;
            /** @var File $file */
            foreach (self::files()->where('relationship', FileSuppressionList::RELATIONSHIP_PARENT)->get() as $file) {
                foreach ($this->statsParent as $key => &$value) {
                    if (!empty($file->{$key})) {
                        $value += $file->{$key};
                    }
                }
            }
        }

        return $this->statsParent;
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
     * @return \Illuminate\Database\Eloquent\Builder|Model|Builder|object|static|null
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
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany
     */
    public function supports()
    {
        return $this->hasMany(SuppressionListSupport::class);
    }
}
