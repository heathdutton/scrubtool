<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlanController extends Controller
{
    use ResetsPasswords;

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = '/files';


    public function __construct()
    {
        // @todo - change to verified.
        $this->middleware(['auth', 'plan']);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        return view('auth.plan', [
            'user'   => $user,
            'intent' => $user->createSetupIntent(),
            'subscriptions' => $user->subscriptions,
        ]);
    }

    /**
     * @param  Request  $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Laravel\Cashier\Exceptions\PaymentActionRequired
     * @throws \Laravel\Cashier\Exceptions\PaymentFailure
     */
    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        if ($request->has('_token')) {
            if (0 === $user->subscriptions->count()) {
                $subscription = $request->get('subscription', 'default');
                $plan         = config('cashier.plan');
                $user->newSubscription($subscription, $plan)
                    ->quantity(0)
                    ->add();
            }
        }

        return view('auth.plan', [
            'user'          => $user,
            'intent'        => $user->createSetupIntent(),
            'subscriptions' => $user->subscriptions,
        ]);
    }
}
