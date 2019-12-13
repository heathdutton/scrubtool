<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;
use Illuminate\Http\Request;

class CaptureToken extends Middleware
{

    /** @var array */
    const TOKEN_KEYS = [
        't',
        'token',
    ];

    /** @var string */
    const TOKEN_SESSION_KEY = 'token';

    /**
     * @param  Request  $request
     * @param  Closure  $next
     * @param  null  $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $previous = session()->get(self::TOKEN_SESSION_KEY);
        foreach (self::TOKEN_KEYS as $key) {
            if ($token = $request->get($key)) {
                $token = self::filter($token);
                if ($token !== $previous) {
                    session()->put(self::TOKEN_SESSION_KEY, $token);
                }
            }
        }

        return $next($request);
    }

    /**
     * @param $token
     *
     * @return false|string
     */
    private static function filter($token)
    {
        return substr(trim($token), 0, 64);
    }

    /**
     * @param $token
     */
    public static function setIfEmpty($token)
    {
        if (!session()->has(self::TOKEN_SESSION_KEY)) {
            $token = self::filter($token);
            if ($token) {
                session()->put(self::TOKEN_SESSION_KEY, $token);
            }
        }
    }
}
