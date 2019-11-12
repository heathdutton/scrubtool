<form method="post" action="{{ route('file.upload') }}"
      id="dropzone"
      enctype="multipart/form-data"
      class="dropzone card bg-light mb-3 col-md-12"
      data-accepted-files=".{{ implode(', .', array_keys(config('excel.extension_detector'))) }}"
      data-max-upload-mb="{{ config('app.max_upload_mb') }}">
    <div class="dz-message">
        <i class="fa fa-arrow-right"></i>
        <span class="p-3">
            {{ __('Drop a file or click to begin') }}
        </span>
        <i class="fa fa-arrow-left"></i>
    </div>
    <div id="previews" class="dropzone-previews"></div>
    @csrf
</form>
<div id="dropzone-preview-template" class="d-none">
    <div class="card border-secondary mb-4">
        <span class="card-header text-secondary" role="tab">
            <div class="file-icon" class="bg-info">
                <div>
                    <img data-dz-thumbnail/>
                </div>
            </div>
            <span data-dz-name class="file-preview-name"></span>
            {{--<i class="fa fa-file-text float-right"></i>--}}
            <span class="float-right">
                <div class="dz-size" data-dz-size></div>
            </span>
        </span>
        <div id="file1" class="collapse show" role="tabpanel" aria-labelledby="heading1">
            <div class="card-body">
                <div class="progress">
                    <div id="file-progress-1" class="progress-bar bg-dark bg-secondary" data-dz-uploadprogress role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                        {{ __('Uploading') }}
                    </div>
                </div>
                {{-- <div class="dz-success-mark fa fa-check text-success"><span></span></div> --}}
                {{-- <div class="dz-error-mark fa fa-stop text-danger"><span></span></div> --}}
                <div class="text-danger"><span data-dz-errormessage></span></div>
            </div>
        </div>
    </div>
</div>
