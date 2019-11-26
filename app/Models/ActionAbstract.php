<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Exceptions\AuditingException;

abstract class ActionAbstract extends Model
{
    use Auditable;

    public $timestamps = false;

    /** @var array */
    protected $guarded = ['id'];

    /**
     * ActionAbstract constructor.
     *
     * @param  array  $attributes
     *
     * @throws AuditingException
     */
    public function __construct(array $attributes = [])
    {
        $this->disableAuditing();

        $attributes['referrer']   = $attributes['referrer'] ?? $this->resolveReferrer() ?? '';
        $attributes['ip_address'] = $attributes['ip_address'] ?? $this->resolveIpAddress();
        $attributes['user_agent'] = $attributes['user_agent'] ?? $this->resolveUserAgent() ?? '';
        $attributes['token']      = $attributes['token'] ?? $this->resolveToken() ?? '';

        if (!isset($attributes['user_id']) && $user = $this->resolveUser()) {
            $attributes['user_id'] = $user->id;
        }

        return parent::__construct($attributes);
    }

    /**
     * Resolve the Referrer if available.
     *
     * @return mixed|null
     */
    protected function resolveReferrer()
    {
        $referrerResolver = Config::get('audit.resolver.referrer');

        return call_user_func([$referrerResolver, 'resolve']);
    }

    /**
     * Resolve the tracking token if available.
     *
     * @return mixed|null
     */
    protected function resolveToken()
    {
        $tokenResolver = Config::get('audit.resolver.token');

        return call_user_func([$tokenResolver, 'resolve']);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

}
