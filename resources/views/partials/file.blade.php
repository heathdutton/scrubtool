<?php
$class  = 'secondary';
$action = '';
$card   = '';
if ($file->status & \App\File::STATUS_ADDED || $file->status & \App\File::STATUS_ANALYSIS) {
    $card   = 'file-refresh';
    $class  = 'secondary';
    $action = __('Analyzing');
} elseif ($file->status & \App\File::STATUS_INPUT_NEEDED) {
    $class  = 'info';
    $action = __('Input Needed');
} elseif ($file->status & \App\File::STATUS_READY) {
    $card   = 'file-refresh';
    $class  = 'secondary';
    $action = __('Ready');
} elseif ($file->status & \App\File::STATUS_RUNNING) {
    $card   = 'file-refresh';
    $class  = 'secondary';
    $action = __('Processing');
} elseif ($file->status & \App\File::STATUS_STOPPED) {
    $class  = 'danger';
    $action = __('Cancelled');
} elseif ($file->status & \App\File::STATUS_WHOLE) {
    $class  = 'success';
    $action = __('Complete');
}
?>
<div class="card border-{{ $class }} mb-4 {{ $card }}" data-file-id="{{ $file->id }}" data-file-status="{{ $file->status }}">
    <a href="#file{{ $file->id }}" class="card-header text-{{ $class }}" role="tab" id="heading{{ $file->id }}" data-toggle="collapse" data-parent="#accordion" aria-expanded="true" aria-controls="file{{ $file->id }}">
        <div style="border-radius: 1.6em;
                    overflow: hidden;
                    display: inline-block;
                    width: 1.6em;
                    height: 1.6em;
                    margin: -0.5em .3em -.5em -.5em;
                    padding: 0;" class="bg-{{ $class }}">
            <div style="border-radius: 1.4em;
                        overflow: hidden;
                        display: inline-block;
                        width: 1.6em;
                        height: 1.6em;
                        margin: 0;
                        padding: 0;">
                <img src="https://www.gravatar.com/avatar/{{ $file->md5 }}?r=pg&d=identicon&s=24"
                     style="filter: grayscale(1) saturate(1.2);
                            transform: rotate(135deg);
                            vertical-align: top;
                            width: 100%;
                            height: 100%;
                            opacity: .9;"
                />
            </div>
        </div>
        {{ $file->name }}
        <i class="fa fa-chevron-down pull-right"></i>
        <span class="pull-right">{{ $action }}</span>
    </a>
    <div id="file{{ $file->id }}" class="collapse show" role="tabpanel" aria-labelledby="heading{{ $file->id }}">
        <div class="card-body">
            @if($file->message)
                <p class="card-text text-{{ $class }}">{{ $file->message }}</p>
            @endif
            @if($file->status & \App\File::STATUS_ADDED || $file->status & \App\File::STATUS_ANALYSIS)
                <div class="progress">
                    <div id="file-progress-{{ $file->id }}" class="progress-bar bg-{{ $class }} progress-bar-auto-4" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                        {{ $action }}
                    </div>
                </div>
            @endif
            {!! form($file->form) !!}
        </div>
    </div>
</div>
