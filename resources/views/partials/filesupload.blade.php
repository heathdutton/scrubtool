<div class="container">
    <form method="post" action="{{url('files/upload')}}" enctype="multipart/form-data" class="dropzone" id="dropzone">
        @csrf
        <div class="dz-message">Drop your spreadsheets here or click to begin</div>
        <div id="previews" class="dropzone-previews"></div>
    </form>
</div>
