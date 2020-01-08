<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Plan extends Middleware
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure  $next
     * @param  mixed  ...$guards
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        if (
            !config('cashier.key')
            || !config('cashier.secret')
            || !config('cashier.plan')
        ) {
            return redirect('/profile');
        }

        return $next($request);
    }
}
