<form method="post" action="{{url('files/upload')}}" enctype="multipart/form-data" class="dropzone card bg-light mb-3" id="dropzone">
    <div class="dz-message">
{{--        <i class="fa fa-3x fa-file-o"></i>--}}
{{--        <i class="fa fa-2x fa-arrow-down"></i>--}}
        Drop a file or click to begin
    </div>
    <div id="previews" class="dropzone-previews"></div>
    @csrf
</form>
