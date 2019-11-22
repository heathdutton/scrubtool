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
                @include('partials.stat', ['icon' => 'align-left', 'class' => '', 'value' => $suppressionList->statParent('rows_imported') ?? 0, 'label' => __('Total Records')])
                @foreach($suppressionList->suppressionListSupports->where('status', \App\SuppressionListSupport::STATUS_READY)->unique('column_type') as $support)
                    @include('partials.stat', [
                        'icon' => __("column_types.icons.{$support->column_type}"),
                        'class' => '',
                        'value' => number_format((new App\SuppressionListContent([], $support))->count()),
                        'label' => __('Unique :type', ['type' => __('column_types.plural.'.$support->column_type)]),
                    ])
                @endforeach
                @include('partials.stat', ['icon' => 'cut', 'class' => '', 'value' => $suppressionList->statParent('rows_scrubbed') ?? 0, 'label' => __('Scrubbed Records')])
            </div>
            <div class="row">
                <div class="col-md-12 mt-3 mb-1">
                    <div class="">
                        <div class="btn-group float-right">
                            <a href="{{ route('defaults', [
                                'action_defaults' => [
                                    'mode' => App\File::MODE_LIST_APPEND,
                                    'suppression_list_append' => $suppressionList->id,
                                ],
                                'target_action' => route('files')
                            ]) }}" class="btn btn-info">
                                <i class="fa fa-plus"></i>
                                {{ __('Append to this List') }}
                            </a>
                            <a href="{{ route('defaults', [
                                'action_defaults' => [
                                    'mode' => App\File::MODE_LIST_REPLACE,
                                    'suppression_list_append' => $suppressionList->id,
                                ],
                                'target_action' => route('files')
                            ]) }}" class="btn btn-warning">
                                <i class="fa fa-plus-square"></i>
                                {{ __('Replace this List') }}
                            </a>
                            <a href="{{ route('defaults', [
                                'action_defaults' => [
                                    'mode' => App\File::MODE_SCRUB,
                                    'suppression_list_use_'.$suppressionList->id => $suppressionList->id,
                                ],
                                'target_action' => route('files')
                            ]) }}" class="btn btn-success">
                                <i class="fa fa-filter"></i>
                                {{ __('Scrub using this List') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
