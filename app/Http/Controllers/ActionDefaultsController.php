<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use App\Helpers\ActionDefaults;

class ActionDefaultsController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function __invoke(Request $request)
    {
        $targetAction = $request->input(ActionDefaults::TARGET_ACTION_PARAM);
        $defaults     = $request->input(ActionDefaults::DEFAULTS_PARAM);

        Session::put(ActionDefaults::DEFAULTS_PARAM, $defaults);

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
