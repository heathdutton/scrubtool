<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
        return view('suppressionLists')->with(['suppressionLists' => $request->user()->suppressionLists]);
    }

    /**
     * @param $id
     * @param  Request  $request
     *
     * @return Factory|JsonResponse|RedirectResponse|View|void
     */
    public function suppressionList($id, Request $request)
    {
        if (!$id) {
            return redirect()->back();
        }

        $suppressionList = $request->user()->suppressionLists->where('id', (int) $id)->first();
        if (!$suppressionList) {
            return abort(404);
        }

        if ($request->ajax()) {
            return response()->json([
                'html'       => view('partials.suppressionLists.item')
                    ->with(['suppressionList' => $suppressionList])
                    ->toHtml(),
                'updated_at' => $suppressionList->updated_at,
                'success'    => true,
            ]);
        } else {
            return view('suppressionLists')->with([
                'suppressionLists' => [$suppressionList],
            ]);
        }

    }
}
