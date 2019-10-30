<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {

        if ($request->user()) {
            return view('lists')->with(['lists' => collect($request->user()->lists()->get())]);
        } else {
            return view('lists');
        }
    }
}
