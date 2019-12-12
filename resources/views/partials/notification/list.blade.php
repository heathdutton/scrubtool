<li class="nav-item dropdown">
    <a id="notifications" class="nav-link" href="{{ route('notificationReadAll') }}" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        @if(Auth::user()->unreadNotifications->count())
            <span class="fa fa-bell has-badge" data-count="{{ Auth::user()->unreadNotifications->count() }}"></span>
        @else
            <span class="fa fa-bell"></span>
        @endif
    </a>
    <ul class="dropdown-menu notify-drop" aria-labelledby="notifications">
        @foreach(Auth::user()->notifications()->limit(10)->get() as $notification)
            @if($notification->data['message'])
                @include('partials.notification.item')
            @endif
        @endforeach
        @include('partials.notification.item', ['notification' => null])
    </ul>
</li>
