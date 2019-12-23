<?php
$class  = 'secondary';
$action = '';
?>
@if(isset($suppressionList->id))
    <div class="card border-{{ $class }} mb-4" data-list-id="{{ $suppressionList->id }}" data-list-status="{{ $suppressionList->status }}">
        <a href="#list{{ $suppressionList->id }}" class="card-header text-{{ $class }}"
           role="tab" id="heading{{ $suppressionList->id }}"
           data-toggle="collapse" data-parent="#accordion" aria-expanded="true" aria-controls="list{{ $suppressionList->id }}">
            {{--            <div class="list-icon altered" class="bg-{{ $class }}">--}}
            {{--                <div>--}}
            {{--                    --}}
            {{--                </div>--}}
            {{--            </div>--}}
            <span>
                {{ $suppressionList->name }}
            </span>
            <i class="fa fa-chevron-down float-right"></i>
            <span class="float-right">{{ $action }}</span>
        </a>
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
            @if($suppressionList->description)
                <div class="row mt-3">
                    <div class="col-md-12 mt-3 mb-1 well">
                        {{ $suppressionList->description }}
                    </div>
                </div>
            @endif
            <div class="row">
                <div class="col-md-12 mt-3 mb-1">
                    <div class="">
                        <div class="btn-group float-right">
                            <a href="{{ route('suppressionList.edit', [
                                'id' => $suppressionList->id,
                            ]) }}" class="btn btn-secondary">
                                <i class="fa fa-pencil"></i>
                                {{ __('Edit') }}
                            </a>
                            <a href="{{ route('defaults', [
                                'action_defaults' => [
                                    'mode' => App\Models\File::MODE_LIST_APPEND,
                                    'suppression_list_append' => $suppressionList->id,
                                ],
                                'target_action' => route('files')
                            ]) }}" class="btn btn-info">
                                <i class="fa fa-plus"></i>
                                {{ __('Append') }}
                            </a>
                            <a href="{{ route('defaults', [
                                'action_defaults' => [
                                    'mode' => App\Models\File::MODE_LIST_REPLACE,
                                    'suppression_list_append' => $suppressionList->id,
                                ],
                                'target_action' => route('files')
                            ]) }}" class="btn btn-warning">
                                <i class="fa fa-plus-square"></i>
                                {{ __('Replace') }}
                            </a>
                            <a href="{{ route('defaults', [
                                'action_defaults' => [
                                    'mode' => App\Models\File::MODE_SCRUB,
                                    'suppression_list_use_'.$suppressionList->id => $suppressionList->id,
                                ],
                                'target_action' => route('files')
                            ]) }}" class="btn btn-success">
                                <i class="fa fa-filter"></i>
                                {{ __('Scrub') }}
                            </a>
                        </div>
                    </div>
                </div>
                @if($suppressionList->form)
                    <div class="col-12">
                        {!! form($suppressionList->form) !!}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
