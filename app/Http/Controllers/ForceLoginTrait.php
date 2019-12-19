<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait ForceLoginTrait
{
    /**
     * @param  Request  $request
     *
     * @return JsonResponse|RedirectResponse|void
     */
    private function forceLogin(Request $request)
    {
        $redirectPath = null;
        if (
            !$request->user()
            && $request->getRequestUri() !== $request->session()->get('url.intended')
        ) {
            // User likely needs to log in.
            $request->session()->put('url.intended', $request->getRequestUri());
            $redirectPath = 'login';
        } else {
            // File no longer exists.
            $redirectPath = 'files';
        }
        if ($redirectPath) {
            if ($request->ajax()) {
                return response()->json([
                    'success'  => true,
                    'redirect' => route($redirectPath),
                ]);
            } else {
                return response()->redirectTo($redirectPath);
            }
        }

        return abort(404);
    }
}
