@extends('layouts.app')

@section('title', 'Files')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="title m-b-md">
                    Upload
                </div>
                @include('partials.filesupload')

                @include('partials.fileslist')
            </div>
        </div>
    </div>
@endsection
