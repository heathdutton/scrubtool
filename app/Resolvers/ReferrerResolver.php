<?php

namespace App\Resolvers;

use Illuminate\Support\Facades\App;
use OwenIt\Auditing\Contracts\UrlResolver;
use Spatie\Referer\Referer;

class ReferrerResolver implements UrlResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve(): string
    {
        if (App::runningInConsole()) {
            return 'console';
        }

        return app(Referer::class)->get() ?? '';
    }
}
