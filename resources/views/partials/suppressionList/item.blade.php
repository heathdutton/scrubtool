<?php
$class = 'secondary';
// $action = '';
?>
@if(isset($suppressionList->id))
    <div class="card border-{{ $class }} mb-4" data-list-id="{{ $suppressionList->id }}" data-list-status="{{ $suppressionList->status }}">
        <span class="card-header text-{{ $class }}"
              role="tab" id="heading{{ $suppressionList->id }}"
              data-toggle="collapse" data-parent="#accordion" aria-expanded="true" aria-controls="list{{ $suppressionList->id }}">
            <span>
                {{ $suppressionList->name }}
            </span>
        </span>
        <div class="card-body">
            <div class="row mt-3">
                @include('partials.stat', ['icon' => 'align-left', 'value' => $suppressionList->statParent('rows_imported') ?? 0, 'label' => __('Total Records')])
                {{--                ->where('status', \App\Models\SuppressionListSupport::STATUS_READY)--}}
                @foreach($suppressionList->suppressionListSupports->sortBy('id')->unique('column_type') as $support)
                    @include('partials.stat', [
                        'icon' => __("column_types.icons.{$support->column_type}"),
                        'value' => number_format((new \App\Models\SuppressionListContent([], $support))->count()),
                        'label' => __('Unique :type', ['type' => __('column_types.plural.'.$support->column_type)]),
                    ])
                @endforeach
                @include('partials.stat', ['icon' => 'filter', 'value' => $suppressionList->statParent('rows_scrubbed') ?? 0, 'label' => __('Scrubbed Records')])
            </div>
            @if($suppressionList->description && !($suppressionList->form && $owner))
                <div class="row mt-3">
                    <div class="col-md-12 mt-3 mb-1 well">
                        {{ $suppressionList->description }}
                    </div>
                </div>
            @endif
            <div class="row">
                @if($suppressionList->form && $owner)
                    <div class="col-12">
                        {!! form($suppressionList->form) !!}
                    </div>
                @else
                    <div class="col-md-12 mt-3 mb-1">
                        <div class="">
                            <div class="btn-group float-right">
                                @if(isset($owner) && $owner)
                                    <a href="{{ route('suppressionList.edit', [
                                        'id' => $suppressionList->id]) }}" class="btn btn-secondary">
                                        <i class="fa fa-pencil"></i>
                                        {{ __('Edit') }}
                                    </a>
                                    <a href="{{ route('defaults', [
                                        'action_defaults' => [
                                            'mode' => App\Models\File::MODE_LIST_REPLACE,
                                            'suppression_list_append' => $suppressionList->id,
                                        ],
                                        'target_action' => route('files')]) }}" class="btn btn-secondary">
                                        <i class="fa fa-plus-square"></i>
                                        {{ __('Replace') }}
                                    </a>
                                    <a href="{{ route('defaults', [
                                        'action_defaults' => [
                                            'mode' => App\Models\File::MODE_LIST_APPEND,
                                            'suppression_list_append' => $suppressionList->id,
                                        ],
                                        'target_action' => route('files')]) }}" class="btn btn-secondary">
                                        <i class="fa fa-plus"></i>
                                        {{ __('Append') }}
                                    </a>
                                    <clipboard-copy value="{{ $suppressionList->getShareRoute() }}"
                                                    aria-label="Copy to clipboard" class="btn btn-secondary"
                                                    tabindex="0" role="button"
                                                    data-toggle='tooltip' data-trigger='manual' data-placement='bottom'
                                                    data-original-title='{{ __(':link was copied to your clipboard. Paste this link to others and they can use this suppression list to scrub their files.', ['link' => $suppressionList->getShareRoute()]) }}'>
                                        <i class="fa fa-share"></i>
                                        {{ __('Share') }}
                                    </clipboard-copy>
                                    <a href="{{ route('defaults', [
                                        'action_defaults' => [
                                            'mode' => App\Models\File::MODE_SCRUB,
                                            'suppression_list_use_'.$suppressionList->id => $suppressionList->id,
                                        ],
                                        'target_action' => route('files')]) }}" class="btn btn-success">
                                        <i class="fa fa-filter"></i>
                                        {{ __('Scrub Using This') }}
                                    </a>
                                @else
                                    <a href="{{ route('defaults', [
                                        'action_defaults' => [
                                            'mode' => App\Models\File::MODE_SCRUB,
                                            'suppression_list_use_'.$suppressionList->id => $suppressionList->getIdToken(),
                                        ],
                                        'target_action' => route('files')]) }}" class="btn btn-success">
                                        <i class="fa fa-filter"></i>
                                        {{ __('Scrub Using This') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
