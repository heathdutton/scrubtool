@extends('layouts.app')

@section('title', 'Upload Files')

@section('content')
    <div class="title m-b-md">
        Upload
    </div>
    @include('partials.filesupload')

    @include('partials.fileslist')
@endsection
