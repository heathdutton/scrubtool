<?php

namespace App\Http\Controllers;

use App\Helpers\ActionDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

class ActionDefaultsController extends Controller
{

    /**
     * @param  Request  $request
     *
     * @return RedirectResponse|Redirector
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
     *
     * @return RedirectResponse|Redirector
     */
    private function returnRedirect($routeString)
    {
        return redirect(Route::has($routeString) ? route($routeString) : $routeString);
    }
}
