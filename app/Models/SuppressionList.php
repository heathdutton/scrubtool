<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Class SuppressionList
 *
 * @package App
 */
class SuppressionList extends Model implements Auditable
{
    use SoftDeletes, AuditableTrait;

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
     * @param  array  $options
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        if (is_null($this->name)) {
            $this->name = __('Untitled');
        }

        if (is_null($this->description)) {
            $this->description = '';
        }

        if (is_null($this->token)) {
            $this->token = $this->generateToken();
        }

        return parent::save($options);
    }


    /**
     * @return string
     */
    private function generateToken()
    {
        $hash   = hash('crc32', uniqid(implode(chr(31), $this->getAttributes())), false);
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

        return number_format($this->statsChildren[$stat]) ?? 0;
    }

    /**
     * @return array
     */
    private function getStatsChildren()
    {
        if (!$this->statsChildren) {
            $this->statsChildren = File::STATS_DEFAULT;
            /** @var File $file */
            foreach ($this->files->where('pivot.relationship',
                FileSuppressionList::REL_LIST_USED_TO_SCRUB) as $file) {
                foreach ($this->statsChildren as $key => &$value) {
                    if (!empty($file->{$key})) {
                        $value += (int) $file->{$key};
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
            ->using(FileSuppressionList::class)
            ->withPivot(['relationship'])
            ->withTrashed();
    }

    /**
     * @param  string  $stat
     *
     * @return int
     */
    public function statParent($stat)
    {
        $this->getStatsParent();

        return number_format($this->statsParent[$stat]) ?? 0;
    }

    /**
     * @return array
     */
    private function getStatsParent()
    {
        if (!$this->statsParent) {
            $this->statsParent = File::STATS_DEFAULT;
            /** @var File $file */
            foreach ($this->files->whereIn('pivot.relationship', [
                FileSuppressionList::REL_FILE_INTO_LIST,
                FileSuppressionList::REL_FILE_REPLACE_LIST,
            ]) as $file) {
                foreach ($this->statsParent as $key => &$value) {
                    if (!empty($file->{$key})) {
                        $value += (int) $file->{$key};
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
    public function suppressionListSupports()
    {
        return $this->hasMany(SuppressionListSupport::class);
    }
}
