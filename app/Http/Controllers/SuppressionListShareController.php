<?php

namespace App\Http\Controllers;

use App\Models\SuppressionList;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

class SuppressionListShareController extends Controller
{

    /**
     * @param $idToken
     * @param  Request  $request
     *
     * @return Factory|JsonResponse|RedirectResponse|Redirector|View|void
     */
    public function share($idToken, Request $request)
    {
        if (!$idToken) {
            return redirect()->back();
        }

        /** @var SuppressionList $suppressionList */
        $suppressionList = SuppressionList::findByIdToken($idToken);
        if (!$suppressionList) {
            return abort(404);
        }

        // You are the owner, go to the regular auth.
        if ($suppressionList->user == $request->user()) {
            return redirect(route('suppressionList', ['id' => $suppressionList->id]));
        }

        if ($suppressionList->private) {
            return abort(401, __('Sorry, this Suppression List has been marked as private.'));
        }

        $view = view('suppressionLists')->with([
            'suppressionLists' => [$suppressionList],
            'owner'            => false,
        ]);
        if ($request->ajax()) {
            return response()->json([
                'html'       => $view->toHtml(),
                'updated_at' => $suppressionList->updated_at,
                'success'    => true,
                'owner'      => false,
            ]);
        }

        return $view;
    }
}
