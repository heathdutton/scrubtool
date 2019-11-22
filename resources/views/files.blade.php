@extends('layouts.app')

@section('title', 'Files')

@section('content')
    <div class="container">
        <div class="col-md-12">
            @if ($upload)
                <div class="justify-content-center mb-3">
                    @include('partials.file.upload')
                </div>
                <h1 id="file-list-header" class="@if(!count($files)) d-none @endif">
                    {{ __('Files') }}
                </h1>
            @elseif (count($files) == 1)
                <h1 id="file-list-header" class="">
                    {{ __('File') }}
                </h1>
            @endif
            <div class="justify-content-center mt-3">
                @include('partials.file.list')
            </div>
        </div>
    </div>
@endsection
