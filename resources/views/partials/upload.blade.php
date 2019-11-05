<form method="post" action="{{ route('file.upload') }}" enctype="multipart/form-data" class="dropzone card bg-light mb-3 col-md-12" id="dropzone">
    <div class="dz-message">
        <i class="fa fa-arrow-right"></i>
        <span class="p-3">
            Drop a file or click to begin
        </span>
        <i class="fa fa-arrow-left"></i>
    </div>
    <div id="previews" class="dropzone-previews"></div>
    @csrf
</form>
