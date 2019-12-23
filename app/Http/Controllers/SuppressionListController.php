<?php

namespace App\Http\Controllers;

use App\Forms\SuppressionListForm;
use App\Models\SuppressionList;
use App\Notifications\SuppressionListDeletedNotification;
use App\Notifications\SuppressionListRestoredNotification;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;
use Kris\LaravelFormBuilder\FormBuilder;

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

        /** @var SuppressionList $suppressionList */
        $suppressionList = SuppressionList::query()
            ->where('id', (int) $id)
            ->where('user_id', (int) $request->user()->id)
            ->first();
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

    /**
     * @param $id
     * @param  Request  $request
     * @param  FormBuilder  $formBuilder
     *
     * @return Factory|JsonResponse|RedirectResponse|View|void
     */
    public function edit($id, Request $request, FormBuilder $formBuilder)
    {
        if (!$id) {
            return redirect()->back();
        }

        /** @var SuppressionList $suppressionList */
        $suppressionList = SuppressionList::query()
            ->where('id', (int) $id)
            ->where('user_id', (int) $request->user()->id)
            ->first();
        if (!$suppressionList) {
            return abort(404);
        }

        $suppressionList->form = $formBuilder->create(SuppressionListForm::class, [], [
            'suppressionList' => $suppressionList,
        ]);

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

    /**
     * @param $id
     * @param  Request  $request
     * @param  FormBuilder  $formBuilder
     *
     * @return RedirectResponse|Redirector|void
     * @throws \Exception
     */
    public function store($id, Request $request, FormBuilder $formBuilder)
    {
        if (!$id) {
            return redirect()->back();
        }

        /** @var SuppressionList $suppressionList */
        $suppressionList = SuppressionList::query()
            ->where('id', (int) $id)
            ->where('user_id', (int) $request->user()->id)
            ->first();
        if (!$suppressionList) {
            return abort(404);
        }

        $suppressionList->form = $formBuilder->create(SuppressionListForm::class, [], [
            'suppressionList' => $suppressionList,
        ]);

        if (!$suppressionList->form->isValid()) {
            return redirect()->back()->withErrors($suppressionList->form->getErrors())->withInput();
        }

        foreach ($suppressionList->form->getFieldValues() as $key => $value) {
            $suppressionList->setAttribute($key, $value);
        }

        if ('true' === $suppressionList->form->getRequest()->get('delete')) {
            $suppressionList->user->notify(new SuppressionListDeletedNotification($suppressionList));
            unset($suppressionList->form);
            $suppressionList->delete();

            return redirect(route('suppressionLists'));
        } else {
            unset($suppressionList->form);
            $suppressionList->save();

            return redirect(route('suppressionList', ['id' => $id]));
        }
    }

    /**
     * @param $id
     * @param  Request  $request
     * @param  FormBuilder  $formBuilder
     *
     * @return RedirectResponse|Redirector|void
     * @throws \Exception
     */
    public function restore($id, Request $request, FormBuilder $formBuilder)
    {
        if (!$id) {
            return redirect()->back();
        }

        /** @var SuppressionList $suppressionList */
        $suppressionList = SuppressionList::onlyTrashed()
            ->where('id', (int) $id)
            ->where('user_id', (int) $request->user()->id)
            ->first();
        if (!$suppressionList) {
            return redirect(route('suppressionLists'));
        }

        $suppressionList->user->notify(new SuppressionListRestoredNotification($suppressionList));
        $suppressionList->restore();

        return redirect(route('suppressionList', ['id' => $id]));
    }
}
