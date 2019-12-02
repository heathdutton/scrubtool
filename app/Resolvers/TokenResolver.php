<?php

namespace App\Resolvers;

use App\Http\Middleware\CaptureToken;
use Illuminate\Support\Facades\App;
use OwenIt\Auditing\Contracts\UrlResolver;

class TokenResolver implements UrlResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve(): string
    {
        if (App::runningInConsole()) {
            return 'console';
        }

        return session()->get(CaptureToken::TOKEN_SESSION_KEY) ?? '';
    }
}
