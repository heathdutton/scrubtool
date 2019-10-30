@extends('layouts.app')

@section('title', 'Files')

@section('content')
    <div class="container">
        <div class="col-md-12">
            <div class="justify-content-center mb-3">
                @include('partials.upload')
            </div>
            <div class="justify-content-center mt-3">
                @include('partials.files')
            </div>
        </div>
    </div>
@endsection
