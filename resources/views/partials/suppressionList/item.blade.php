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
            <div class="row">
                @include('partials.stat', ['icon' => 'database', 'class' => '', 'value' => $suppressionList->statParent('rows_imported') ?? 0, 'label' => __('Total Records')])
            </div>
            <div class="row">
                @foreach($suppressionList->suppressionListSupports->where('status', \App\SuppressionListSupport::STATUS_READY)->unique('column_type') as $support)
                    <div class="col-xl-3 col-lg-6 col-12 mb-3">
                        <div class="card ">
                            <div class="card-content">
                                <div class="card-body">
                                    <div class="media d-flex">
                                        <div class="align-self-center">
                                            <i class="fa fa-{{ __("column_types.icons.{$support->column_type}") }} fa-4x pull-left "></i>
                                        </div>
                                        <div class="media-body text-right">
                                            <h3>{{ __("column_types.{$support->column_type}") }}</h3>
                                            <span>
                                                {{ (new App\SuppressionListContent([], $support))->count() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="row">
                <div class="col-md-12 mt-3 mb-1">
                    <div class="">
                        <div class="btn-group float-right">
                            <a href="/defaults?action_defaults[mode]={{ App\File::MODE_LIST_APPEND }}&action_defaults[suppression_list_append]={{ $suppressionList->id }}&target_action={{ route('files') }}" class="btn btn-info">
                                <i class="fa fa-plus"></i>
                                {{ __('Append to this List') }}
                            </a>
                            <a href="/defaults?action_defaults[mode]={{ App\File::MODE_LIST_REPLACE }}&action_defaults[suppression_list_replace]={{ $suppressionList->id }}&target_action={{ route('files') }}" class="btn btn-warning">
                                <i class="fa fa-plus"></i>
                                {{ __('Replace this List') }}
                            </a>
                            <a href="/defaults?action_defaults[mode]={{ App\File::MODE_SCRUB }}&target_action=/files" class="btn btn-success">
                                <i class="fa fa-cut"></i>
                                {{ __('Scrub Using this List') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
