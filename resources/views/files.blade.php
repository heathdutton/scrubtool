@extends('layouts.app')

@section('title', 'Files')

@section('content')
    <div class="container">
        <div class="col-md-12">
            @if ($upload)
                <div class="justify-content-center mb-3">
                    @include('partials.upload')
                </div>
                @if(count($files))
                    <h1>{{ __('Your Files') }}</h1>
                @endif
            @endif
            <div class="justify-content-center mt-3">
                @include('partials.files')
            </div>
        </div>
    </div>
@endsection
