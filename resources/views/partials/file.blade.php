<?php
$class  = 'secondary';
$action = '';
if ($file->status & \App\File::STATUS_ADDED) {
    $class  = 'secondary';
    $action = 'Upload Successful';
} elseif ($file->status & \App\File::STATUS_ANALYSIS) {
    $class  = 'secondary';
    $action = 'Analyzing';
} elseif ($file->status & \App\File::STATUS_INPUT_NEEDED) {
    $class  = 'info';
    $action = 'Input Needed';
} elseif ($file->status & \App\File::STATUS_READY) {
    $class  = 'secondary';
    $action = 'Ready';
} elseif ($file->status & \App\File::STATUS_RUNNING) {
    $class  = 'secondary';
    $action = 'Processing';
} elseif ($file->status & \App\File::STATUS_STOPPED) {
    $class  = 'danger';
    $action = 'Cancelled';
} elseif ($file->status & \App\File::STATUS_WHOLE) {
    $class  = 'success';
    $action = 'Complete';
}
?>
<div class="card border-{{ $class }} mb-4">

    <a href="#file{{ $file->id }}" class="card-header text-{{ $class }}" role="tab" id="heading{{ $file->id }}" data-toggle="collapse" data-parent="#accordion" aria-expanded="true" aria-controls="file{{ $file->id }}">
        {{ $file->name }}
        <i class="fa fa-chevron-down pull-right"></i>
        <span class="pull-right">{{ $action }}</span>
    </a>

    <div id="file{{ $file->id }}" class="collapse show" role="tabpanel" aria-labelledby="heading{{ $file->id }}">
        <div class="card-body">
            <p class="card-text">{{ $file->message }}</p>
            <form class="bs-component">
                <fieldset>
                    <legend>{{ __('File Actions') }}</legend>
                    <div class="form-group">
                        {!! form($file->form) !!}
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
</div>
