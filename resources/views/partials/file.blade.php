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
        <div style="border-radius: 1.5em;
                    overflow: hidden;
                    display: inline-block;
                    width: 1.6em;
                    height: 1.6em;
                    margin: -0.5em .3em -.5em -.5em;
                    padding: 0;" class="bg-{{ $class }}">
            <div style="border-radius: 1.4em;
                        overflow: hidden;
                        display: inline-block;
                        width: 1.4em;
                        height: 1.4em;
                        margin: .11em 0 0 .1em;
                        padding: 0;">
                <img src="https://www.gravatar.com/avatar/{{ $file->md5 }}?r=pg&d=identicon&s=24"
                     style="filter: grayscale(1) saturate(1.2);
                    transform: rotate(135deg);
                    vertical-align: top;
                    width: 100%;
                    height: 100%;
                    opacity: .9;">
            </div>
        </div>
        {{ $file->name }}
        <i class="fa fa-chevron-down pull-right"></i>
        <span class="pull-right">{{ $action }}</span>
    </a>

    <div id="file{{ $file->id }}" class="collapse show" role="tabpanel" aria-labelledby="heading{{ $file->id }}">
        <div class="card-body">
            <p class="card-text">{{ $file->message }}</p>
            <form class="bs-component">
                <fieldset>
                    <div class="form-group">
                        {!! form($file->form) !!}
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
</div>
