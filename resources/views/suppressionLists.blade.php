@extends('layouts.app')

@section('title', 'Suppression Lists')

@section('content')
    <div class="container">
        <div class="col-md-12">
            @if(count($suppressionLists) > 1)
                <h1>{{ __('Suppression Lists') }}</h1>
            @else
                <h1>{{ __('Suppression List') }}</h1>
            @endif
            <div class="justify-content-center mt-3">
                @include('partials.suppressionList.list')
            </div>
        </div>
    </div>
@endsection
