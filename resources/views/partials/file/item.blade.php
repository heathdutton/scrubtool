<?php
$class  = 'secondary';
$action = '';
$card   = '';
if ($file->status & \App\Models\File::STATUS_ADDED) {
    $card   = 'file-refresh';
    $class  = 'secondary';
    $action = __('Queued');
} elseif ($file->status & \App\Models\File::STATUS_ANALYSIS) {
    $card   = 'file-refresh';
    $class  = 'secondary';
    $action = __('Analyzing');
} elseif ($file->status & \App\Models\File::STATUS_INPUT_NEEDED) {
    $class  = 'info';
    $action = __('Setup');
} elseif ($file->status & (\App\Models\File::STATUS_READY | \App\Models\File::STATUS_RUNNING)) {
    $card  = 'file-refresh';
    $class = 'secondary';
    if ($file->mode & \App\Models\File::MODE_HASH) {
        $action = __('Hashing');
    } elseif ($file->mode & \App\Models\File::MODE_SCRUB) {
        $action = __('Scrubbing');
    } elseif ($file->mode & \App\Models\File::MODE_LIST_APPEND) {
        $action = __('Appending Suppression List');
    } elseif ($file->mode & \App\Models\File::MODE_LIST_CREATE) {
        $action = __('Creating Suppression List');
    } elseif ($file->mode & \App\Models\File::MODE_LIST_REPLACE) {
        $action = __('Replacing Suppression List');
    }
} elseif ($file->status & \App\Models\File::STATUS_STOPPED) {
    $class  = 'danger';
    $action = __('Cancelled');
} elseif ($file->status & \App\Models\File::STATUS_WHOLE) {
    $class = 'success';
    if ($file->mode & \App\Models\File::MODE_HASH) {
        $action = __('File Hashed');
    } elseif ($file->mode & \App\Models\File::MODE_SCRUB) {
        $action = __('File Scrubbed');
    } elseif ($file->mode & \App\Models\File::MODE_LIST_APPEND) {
        $action = __('Suppression List Appended');
    } elseif ($file->mode & \App\Models\File::MODE_LIST_CREATE) {
        $action = __('Suppression List Created');
    } elseif ($file->mode & \App\Models\File::MODE_LIST_REPLACE) {
        $action = __('Suppression List Replaced');
    }
}
?>
<div class="card border-{{ $class }} mb-4 {{ $card }} card-file"
     data-file-id="{{ $file->id }}"
     data-file-status="{{ $file->status }}"
     data-file-origin="{{ route('file', ['id' => $file->id]) }}"
     data-updated-at="{{ $file->updated_at->format(\App\Models\File::DATE_FORMAT) }}"
>
    <a href="#file{{ $file->id }}" class="card-header text-{{ $class }}"
       role="tab" id="heading{{ $file->id }}"
       data-toggle="collapse" data-parent="#accordion" aria-expanded="true" aria-controls="file{{ $file->id }}">
        <div class="file-icon altered" class="bg-{{ $class }}">
            <div>
                @if($file->md5)
                    <img src="https://www.gravatar.com/avatar/{{ $file->md5 }}?r=pg&d=identicon&s=24"/>
                @else
                    <i class="fa fa-question-circle fa-2x" style="margin: -2px 0 0 -.5px; opacity: .2; filter: grayscale(1) saturate(1.2);"></i>
                @endif
            </div>
        </div>
        <span data-toggle='tooltip' data-placement="top"
              data-original-title='<dl>
                <dt>{{ __('Size')  }}</dt><dd>{{ $file->humanSize() }}</dd>
                <dt>{{ __('Added')  }}</dt><dd>{{ $file->created_at }}</dd>
                @if($file->md5)
                  <dt>{{ __('MD5')  }}</dt><dd>{{ $file->md5 }}</dd>
                @endif
              @if($file->crc32b)
                  <dt>{{ __('CRC32b')  }}</dt><dd>{{ $file->crc32b }}</dd>
                @endif
              @if($file->column_count)
                  <dt>{{ __('Columns')  }}</dt><dd>{{ $file->stat('column_count') }}</dd>
                @endif
              @if($file->rows_total)
                  <dt>{{ __('Total Rows')  }}</dt><dd>{{ $file->stat('rows_total') }}</dd>
                @endif
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
            @if($file->status & \App\Models\File::STATUS_ADDED || $file->status & \App\Models\File::STATUS_ANALYSIS || $file->status & \App\Models\File::STATUS_READY || $file->status & \App\Models\File::STATUS_RUNNING)
                <div class="progress">
                    <div class="progress-bar bg-dark bg-{{ $class }} progress-bar-animated progress-bar-striped" role="progressbar" style="width: {{ $file->progress() }}%">
                        <time datetime="{{ $file->eta() }}" class="eta countdown text-center ml-3 mr-3" style="opacity: 0;"></time>
                    </div>
                </div>
            @endif
            @if($file->status & (\App\Models\File::STATUS_WHOLE | \App\Models\File::STATUS_RUNNING))
                <div class="row mt-3">
                    @include('partials.stat', ['icon' => 'align-justify', 'class' => '', 'value' => $file->stat('rows_total'), 'id' => 'rows_total', 'label' => __('Rows')])
                    @include('partials.stat', ['icon' => 'align-left', 'class' => '', 'value' => $file->stat('rows_filled'), 'id' => 'rows_filled', 'label' => __('Records')])
                    @include('partials.stat', ['icon' => 'close', 'class' => 'text-warning', 'value' => $file->stat('rows_invalid'), 'id' => 'rows_invalid', 'label' => __('Invalid')])
                    @include('partials.stat', ['icon' => 'hashtag', 'class' => 'text-success', 'value' => $file->stat('rows_hashed'), 'id' => 'rows_hashed', 'label' => __('Hashed')])
                    @include('partials.stat', ['icon' => 'filter', 'class' => 'text-success', 'value' => $file->stat('rows_scrubbed'), 'id' => 'rows_scrubbed', 'label' => __('Scrubbed')])
                    @include('partials.stat', ['icon' => 'check', 'class' => 'text-success', 'value' => $file->stat('rows_imported'), 'id' => 'rows_imported', 'label' => __('Imported')])
                    @include('partials.stat', ['icon' => 'download', 'class' => '', 'value' => number_format($file->downloads->count()), 'id' => '', 'label' => __('Downloads')])
                </div>
                <div class="row">
                    <div class="col-md-12 mt-3 mb-1">
                        <div class="">
                            <div class="btn-group float-right">
                                @if($file->mode & (\App\Models\File::MODE_SCRUB | \App\Models\File::MODE_HASH))
                                    @if($file->status & \App\Models\File::STATUS_WHOLE)
                                        {{-- @todo - This needs contextual awareness --}}
                                        <a class="btn btn-secondary"
                                           href="{{ route('files') }}"
                                           onclick="
                                        var $dropzone = $('#dropzone:first');
                                        if ($dropzone.length) {
                                            $dropzone.click();
                                            return false;
                                        }" style="white-space: nowrap;">
                                            <i class="fa fa-plus"></i>
                                            {{ __('Another like this') }}
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
                                @endif

                                @if($file->mode & (\App\Models\File::MODE_LIST_APPEND | \App\Models\File::MODE_LIST_CREATE | \App\Models\File::MODE_LIST_REPLACE))
                                    @if($file->status & \App\Models\File::STATUS_WHOLE)
                                        @if ($suppressionList = $file->suppressionLists->whereIn('pivot.relationship',[\App\Models\FileSuppressionList::REL_FILE_INTO_LIST, \App\Models\FileSuppressionList::REL_FILE_REPLACE_LIST])->first())
                                            <a class="form-control btn btn-{{ $class }}"
                                               href="{{ route('suppressionList', [
                                                'id' => $suppressionList->id
                                            ]) }}">
                                                <i class="fa fa-list"></i>
                                                {{ __('Suppression List') }}
                                            </a>
                                        @endif
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if($file->form)
                <div class="row">
                    <div class="col-md-12">
                        {!! form($file->form) !!}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
