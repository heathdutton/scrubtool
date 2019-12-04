<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->check() ? auth()->user()->id : '' }}">
    <meta name="pusher-key" content="{{ config('broadcasting.connections.pusher.key') }}">
    <meta name="pusher-cluster" content="{{ config('broadcasting.connections.pusher.options.cluster') }}">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <script src="{{ asset('js/app.js') }}" defer></script>
    @if(config('theme.external_css'))
        <link href="{{ config('theme.external_css') }}" rel="stylesheet">
    @endif
    @yield('head')
</head>
<body>
<div id="app">
    <nav class="navbar navbar-expand-md navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">
                @include('partials.logo')
                {{ config('app.name', 'Laravel') }}
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav mr-auto">
                    @if (Route::has('files'))
                        <li class="nav-item">
                            <a class="nav-link {{ Request::segment(1) === 'files' ? 'active' : null }}" href="{{ route('files') }}">
                                <i class="fa fa-files-o"></i>
                                {{ __('Files') }}
                            </a>
                        </li>
                    @endif
                    @if (Route::has('suppressionLists'))
                        <li class="nav-item">
                            <a class="nav-link {{ Request::segment(1) === 'lists' ? 'active' : null }}" href="{{ route('suppressionLists') }}">
                                <i class="fa fa-list"></i>
                                {{ __('Lists') }}
                            </a>
                        </li>
                    @endif
                </ul>
                <ul class="navbar-nav ml-auto">
                    @guest
                        @if (Route::has('register'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                            </li>
                        @endif
                        @if (Route::has('login'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                            </li>
                        @endif
                    @else
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                @isset(Auth::user()->email)
                                    <img
                                        src="https://secure.gravatar.com/avatar/{{ md5(strtolower(Auth::user()->email)) }}?size=512&d=mp"
                                        class="rounded-full w-8 h-8 mr-2"
                                        style="width: 1.6em; height: 1.6em; margin-top: -.3em;"
                                    />
                                @endisset
                                {{ Auth::user()->name }} <span class="caret"></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                @if(Auth::user()->hasRole('admin'))
                                    <a class="dropdown-item" href="{{ url('admin') }}">
                                        <i class="fa fa-cog"></i>
                                        {{ __('Admin Panel') }}
                                    </a>
                                @endif
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                    <i class="fa fa-key"></i>
                                    {{ __('Logout') }}
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                            </div>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>
    <main class="py-5">
        @yield('content')
    </main>
</div>
<footer class="navbar navbar-light navbar-expand-md">
    <div class="container text-muted">
        <a class="navbar-brand text-muted" href="{{ url('/') }}">
            @include('partials.logo', ['fill' => '#919aa1'])
            {{ config('app.name', 'Laravel') }}
        </a>
        <ul class="navbar-nav ml-auto">
            @if(config('theme.repo'))
                <li class="nav-item">
                    <a class="nav-link" href="{{ config('theme.repo_link') }}">
                        <i class="fa fa-github"></i>
                        {{ config('theme.repo') }}
                    </a>
                </li>
            @endif
            <li class="nav-item">
                <a @if(config('theme.copyright_link')) class="nav-link" href="{{ config('theme.copyright_link') }}" @endif>
                    <i class="fa fa-copyright"></i>
                    {{ date('Y') }} {{ config('theme.copyright') }}
                </a>
            </li>
        </ul>
    </div>
</footer>
@include('partials.gtm')
</body>
</html>
