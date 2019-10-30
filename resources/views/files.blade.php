@extends('layouts.app')

@section('title', 'Files')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                @include('partials.filesupload')

                <br/>

                @include('partials.fileslist')
            </div>
        </div>
    </div>
@endsection
