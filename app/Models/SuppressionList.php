<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
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

    const TOKEN_SEP = 'z';

    public $casts = [
        'private'  => 'boolean',
        'required' => 'boolean',
        'global'   => 'boolean',
    ];

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
     * @param $idTokens
     *
     * @return array
     */
    public static function findByIdTokens($idTokens)
    {
        $results  = [];
        $idTokens = self::parseIdTokens($idTokens);
        if ($idTokens) {
            $q = self::query();
            foreach ($idTokens as $id => $token) {
                $method = 'where';
                $q->{$method}(function ($query) use ($id, $token) {
                    $query->where('id', $id)
                        ->where('token', $token);
                });
                $method = 'orWhere';
            }
            $results = $q->limit(count($idTokens))->get();
        }

        return $results;
    }

    /**
     * @param $idTokens
     *
     * @return array
     */
    public static function parseIdTokens($idTokens)
    {
        $result = [];
        foreach ($idTokens as $idToken) {
            $idToken = strtolower(trim($idToken));
            if (false === strpos($idToken, self::TOKEN_SEP)) {
                continue;
            }
            [$idString, $tokenString] = explode(self::TOKEN_SEP, $idToken);
            if (!strlen($idString) || !strlen($tokenString)) {
                continue;
            }

            $id = (int) base_convert($idString, 35, 10);
            if (!$id) {
                continue;
            }
            $result[$id] = $tokenString;
        }

        return $result;
    }

    /**
     * @param $string
     *
     * @return int
     */
    public static function getIdFromString($string)
    {
        $idToken = strtolower(trim($string));
        if (false !== strpos($idToken, self::TOKEN_SEP)) {
            [$idString, $tokenString] = explode(self::TOKEN_SEP, $idToken);
            if (strlen($idString)) {
                return (int) $idString;
            }
        }

        return (int) $string;
    }

    /**
     * @param $array
     * @param  User|null  $user
     *
     * @return \Illuminate\Database\Eloquent\Builder[]|Collection
     */
    public static function findByIdTokensOrUserOrGlobal($array, User $user = null)
    {
        $results  = new Collection();
        $idTokens = self::parseIdTokens($array);
        $ids      = array_diff_key($array, $idTokens);
        if ($idTokens || $ids) {
            $results = self::query()
                ->where(function ($q) use ($idTokens, $ids, $user) {
                    $method = 'where';
                    foreach ($idTokens as $id => $token) {
                        $q->{$method}(function ($q) use ($id, $token, $user) {
                            // Shared suppression lists
                            $q->where('id', $id)
                                ->where('token', $token)
                                ->where(function ($q) use ($user) {
                                    $q->where('private', 0)
                                        ->orWhere('global', 1);
                                    if ($user) {
                                        $q->orWhere('user_id', $user->id);
                                    }
                                });
                        });
                        $method = 'orWhere';
                    }
                    if ($ids) {
                        $q->{$method}(function ($q) use ($ids, $user) {
                            // Global suppression lists.
                            $q->whereIn('id', $ids)
                                ->where(function ($q) use ($ids, $user) {
                                    // Owned suppression lists.
                                    $q->where('global', 1);
                                    if ($user) {
                                        $q->orwhere('user_id', $user->id);
                                    }
                                });
                        });
                    }
                })
                ->limit(count($idTokens) + count($ids))
                ->get();
        }

        return $results;
    }

    /**
     * @param $idToken
     *
     * @return \Illuminate\Database\Eloquent\Builder|Model|Builder|object|static|null
     */
    public static function findByIdToken($idToken)
    {
        $idToken = strtolower(trim($idToken));
        if (false === strpos($idToken, self::TOKEN_SEP)) {
            return null;
        }
        [$idString, $tokenString] = explode(self::TOKEN_SEP, $idToken);
        if (!strlen($idString) || !strlen($tokenString)) {
            return null;
        }

        $id = (int) base_convert($idString, 35, 10);
        if (!$id) {
            return null;
        }

        return self::query()
            ->where('id', $id)
            ->where('token', $tokenString)
            ->first();
    }

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
     * @return HasMany
     */
    public function actions()
    {
        return $this->hasMany(ActionAbstract::class);
    }

    /**
     * @return string
     */
    public function getShareRoute()
    {
        static $route;

        if (!$route) {
            $route = route('suppressionList.share', ['idToken' => $this->getIdToken()]);
            if (
                ($appUrl = config('app.url'))
                && ($appShortUrl = config('app.short_url'))
                && $appUrl !== $appShortUrl
            ) {
                $route = str_ireplace($appUrl, $appShortUrl, $route);
            }
        }

        return $route;
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

        return $idString.self::TOKEN_SEP.$this->token;
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
