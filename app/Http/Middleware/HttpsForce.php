<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Request;

class HttpsForce
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (
            config('https.force', false)
            && App::environment() === 'production'
        ) {
            if ('https' === $request->server('HTTP_X_FORWARDED_PROTO')) {
                // Trust reverse proxies to prevent redirect loops.
                $request->setTrustedProxies([$request->getClientIp()], Request::HEADER_X_FORWARDED_PROTO);
            }
            if (!$request->secure()) {
                // Force redirection from http to https.
                return redirect()->secure($request->getRequestUri(), 301);
            }
            // Force https to be rendered by all view links.
            URL::forceScheme('https');
        }

        return $next($request);
    }
}
