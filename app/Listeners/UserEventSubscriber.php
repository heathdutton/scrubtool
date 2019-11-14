<?php

namespace App\Listeners;

use App\File;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;

class UserEventSubscriber
{

    const PREVIOUS_SESSION_ID = 'previous_session_id';

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Handle user login events.
     */
    public function onUserLogin($event)
    {
        $sessionId         = $this->request->getSession()->getId();
        $sessionIdPrevious = $this->request->getSession()->get(self::PREVIOUS_SESSION_ID);
        if ($sessionId !== $sessionIdPrevious && $event->user->id) {
            File::addUserToFilesBySessionId($sessionIdPrevious, $event->user->id);
        }
    }

    /**
     * Handle user logout events.
     */
    public function onUserLogout($event)
    {
    }

    /**
     * Handle user logout events.
     */
    public function onAttempting($event)
    {
        $sessionId = $this->request->getSession()->getId();
        $this->request->getSession()->put(self::PREVIOUS_SESSION_ID, $sessionId);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'Illuminate\Auth\Events\Login',
            'App\Listeners\UserEventSubscriber@onUserLogin'
        );

        $events->listen(
            'Illuminate\Auth\Events\Attempting',
            'App\Listeners\UserEventSubscriber@onAttempting'
        );

        $events->listen(
            'Illuminate\Auth\Events\Logout',
            'App\Listeners\UserEventSubscriber@onUserLogout'
        );
    }
}
