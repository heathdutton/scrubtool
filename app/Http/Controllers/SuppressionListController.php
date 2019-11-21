<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SuppressionListController extends Controller
{

    /**
     * SuppressionListController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param  Request  $request
     *
     * @return Factory|View
     */
    public function index(Request $request)
    {

        if ($request->user()) {
            return view('lists')->with(['lists' => $request->user()->suppressionLists]);
        } else {
            return view('lists');
        }
    }
}
