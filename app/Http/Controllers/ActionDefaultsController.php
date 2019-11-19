<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

class ActionDefaultsController extends Controller
{
    const TARGET_ACTION_PARAM = 'target_action';
    const DEFAULTS_PARAM      = 'action_defaults';

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function __invoke(Request $request)
    {
        $targetAction = $request->input(self::TARGET_ACTION_PARAM);
        $defaults     = $request->input(self::DEFAULTS_PARAM);

        Session::put(self::DEFAULTS_PARAM, $defaults);

        return $this->returnRedirect($targetAction);
    }

    /**
     * @param $routeString
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    private function returnRedirect($routeString)
    {
        return redirect(Route::has($routeString) ? route($routeString) : $routeString);
    }
}
