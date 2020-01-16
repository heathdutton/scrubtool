<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Auditable;

/**
 * Class ActionAbstract
 *
 * Used as an abstract model for web actions made by end-users
 * that we wish to audit without limits/truncation. Examples include uploads and downloads.
 *
 * @package App\Models
 */
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

        return parent::__construct($attributes);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            /** @var ActionAbstract $model */
            $model->referrer   = $model->referrer ?? $model->resolveReferrer() ?? '';
            $model->ip_address = $model->ip_address ?? $model->resolveIpAddress();
            $model->user_agent = $model->user_agent ?? $model->resolveUserAgent() ?? '';
            $model->token      = $model->token ?? $model->resolveToken() ?? '';
            if (!isset($model->user_id) && $user = $model->resolveUser()) {
                $model->user_id = $user->id;
            }
        });
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

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @param  array  $attributes
     *
     * @return ActionSummary
     */
    public function hourly($attributes = [])
    {
        return new ActionSummary($attributes, $this, ActionSummary::TYPE_HOURLY);
    }

    /**
     * @param  array  $attributes
     *
     * @return ActionSummary
     */
    public function daily($attributes = [])
    {
        return new ActionSummary($attributes, $this, ActionSummary::TYPE_DAILY);
    }
}
