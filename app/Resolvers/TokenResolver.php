<?php

namespace App\Resolvers;

use App\Http\Middleware\TokenCapture;
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

        return session()->get(TokenCapture::TOKEN_SESSION_KEY) ?? '';
    }
}
