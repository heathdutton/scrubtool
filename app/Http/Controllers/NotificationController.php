<?php

namespace App\Http\Controllers;

use App\Models\User;

class NotificationController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function readAll()
    {

        /** @var User $user */
        if ($user = auth()->user()) {
            $user->unreadNotifications->markAsRead();
        };

        return response()->json([
            'success' => true,
        ]);
    }
}
