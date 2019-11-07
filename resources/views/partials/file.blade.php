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
    $action = __('Setup');
} elseif ($file->status & \App\File::STATUS_READY || $file->status & \App\File::STATUS_RUNNING) {
    $card  = 'file-refresh';
    $class = 'secondary';
    if ($file->mode & \App\File::MODE_HASH) {
        $action = __('Hashing');
    } elseif ($file->mode & \App\File::MODE_SCRUB) {
        $action = __('Scrubbing');
    } elseif ($file->mode & \App\File::MODE_LIST_APPEND) {
        $action = __('Appending Suppression List');
    } elseif ($file->mode & \App\File::MODE_LIST_CREATE) {
        $action = __('Creating Suppression List');
    } elseif ($file->mode & \App\File::MODE_LIST_REPLACE) {
        $action = __('Replacing Suppression List');
    }
} elseif ($file->status & \App\File::STATUS_STOPPED) {
    $class  = 'danger';
    $action = __('Cancelled');
} elseif ($file->status & \App\File::STATUS_WHOLE) {
    $class  = 'success';
    $action = __('Done');
}
?>
<div class="card border-{{ $class }} mb-4 {{ $card }}" data-file-id="{{ $file->id }}" data-file-status="{{ $file->status }}">
    <a href="#file{{ $file->id }}" class="card-header text-{{ $class }}"
       role="tab" id="heading{{ $file->id }}"
       data-toggle="collapse" data-parent="#accordion" aria-expanded="true" aria-controls="file{{ $file->id }}">
        <div class="file-icon altered" class="bg-{{ $class }}">
            <div>
                <img src="https://www.gravatar.com/avatar/{{ $file->md5 }}?r=pg&d=identicon&s=24"/>
            </div>
        </div>
        <span data-toggle='tooltip' data-placement="top"
              data-original-title='<dl>
                                        <dt>{{ __('Size')  }}</dt>
                                        <dd>{{ $file->humanSize() }}</dd>
                                        <dt>{{ __('Added')  }}</dt>
                                        <dd>{{ $file->created_at }}</dd>
                                        <dt>{{ __('MD5')  }}</dt>
                                        <dd>{{ $file->md5 }}</dd>
                                        <dt>{{ __('CRC32b')  }}</dt>
                                        <dd>{{ $file->crc32b }}</dd>
                                        <dt>{{ __('Columns')  }}</dt>
                                        <dd>{{ $file->column_count }}</dd>
                                    </dl>'>
            {{ $file->name }}
        </span>
        <i class="fa fa-chevron-down float-right"></i>
        <span class="float-right">{{ $action }}</span>
    </a>
    <div id="file{{ $file->id }}" class="collapse show" role="tabpanel" aria-labelledby="heading{{ $file->id }}">
        <div class="card-body">
            @if($file->message)
                <p class="card-text text-{{ $class }}">{{ $file->message }}</p>
            @endif

            @if($file->status & \App\File::STATUS_ADDED || $file->status & \App\File::STATUS_ANALYSIS || $file->status & \App\File::STATUS_READY || $file->status & \App\File::STATUS_RUNNING)
                <div class="progress">
                    <div id="file-progress-{{ $file->id }}" class="progress-bar bg-dark bg-{{ $class }} progress-bar-animated progress-bar-striped" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                        {{ $action }}
                    </div>
                </div>
            @else
            @endif
            @if($file->form)
                {!! form($file->form) !!}
            @endif
            @if($file->status & \App\File::STATUS_WHOLE)
                <div class="row">
                    @include('partials.stat', ['icon' => 'database', 'class' => '', 'value' => $file->rows_total, 'label' => __('Total Records')])
                    @include('partials.stat', ['icon' => 'bug', 'class' => '', 'value' => $file->rows_invalid, 'label' => __('Invalid')])
                    @if($file->mode & \App\File::MODE_HASH)
                        @include('partials.stat', ['icon' => 'hashtag', 'class' => '', 'value' => $file->rows_hashed, 'label' => __('Hashed')])
                    @endif
                    @if($file->mode & \App\File::MODE_SCRUB)
                        @include('partials.stat', ['icon' => 'remove', 'class' => '', 'value' => $file->rows_scrubbed, 'label' => __('Scrubbed')])
                    @endif
                    @if($file->mode & (\App\File::MODE_HASH | \App\File::MODE_SCRUB))
                        @include('partials.stat', ['icon' => 'download', 'class' => '', 'value' => $file->download_count, 'label' => __('Downloads')])
                    @endif
                </div>
                <div class="row">
                    <div class="col-12 mt-3 mb-1">
                        <div class="">
                            <div class="input-group float-right" style="max-width: 480px;">
                                @if($file->mode & (\App\File::MODE_SCRUB | \App\File::MODE_HASH))
                                    {{-- @todo - This needs contextual awareness --}}
                                    <a class="btn btn-secondary"
                                       href="{{ route('files') }}"
                                       onclick="
                                        var $dropzone = $('#dropzone:first');
                                        if ($dropzone.length) {
                                            $dropzone.click();
                                            return false;
                                        }">
                                        <i class="fa fa-plus"></i>
                                        {{ __('Another') }}
                                    </a>
                                    @if($file->available_till)
                                        <div class="input-group-prepend"
                                             data-toggle='tooltip' data-placement="bottom"
                                             data-original-title='{{ __('File is available till') }} {{ $file->available_till }} UTC'>
                                        <span class="input-group-text" id="file-dl-{{ $file->id }}">
                                            <time datetime="{{ $file->available_till }} UTC" class="countdown" style="opacity: 0;">xh xxm xxs</time>
                                        </span>
                                        </div>
                                    @endif
                                    <a class="form-control btn btn-{{ $class }}" aria-describedby="file-dl-{{ $file->id }}"
                                       target="_blank"
                                       href="{{ route('file.download', ['id' => $file->id]) }}"
                                       onclick="
                                           $('<iframe/>').attr({
                                           src: '{{ route('file.download', ['id' => $file->id]) }}',
                                           style: 'visibility:hidden; display:none'
                                           }).appendTo(body); return false;">
                                        <i class="fa fa-download"></i>
                                        {{ __('Download') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
