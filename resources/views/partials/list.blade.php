<?php
$class  = 'secondary';
$action = '';
?>
@if(isset($list->id))
    <div class="card border-{{ $class }} mb-4" data-list-id="{{ $list->id }}" data-list-status="{{ $list->status }}">
        <a href="#list{{ $list->id }}" class="card-header text-{{ $class }}"
           role="tab" id="heading{{ $list->id }}"
           data-toggle="collapse" data-parent="#accordion" aria-expanded="true" aria-controls="list{{ $list->id }}">
            {{--            <div class="list-icon altered" class="bg-{{ $class }}">--}}
            {{--                <div>--}}
            {{--                    --}}
            {{--                </div>--}}
            {{--            </div>--}}
            <span>
                {{ $list->name }}
            </span>
            <i class="fa fa-chevron-down float-right"></i>
            <span class="float-right">{{ $action }}</span>
        </a>
        <div class="card-body">
            <div class="row">
                @include('partials.stat', ['icon' => 'database', 'class' => '', 'value' => $list->statParent('rows_imported') ?? 0, 'label' => __('Total Records')])
            </div>
            <div class="row">
                @foreach($list->suppressionListSupports->where('status', \App\SuppressionListSupport::STATUS_READY)->unique('column_type') as $support)
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
                <div class="col-xl-5 col-lg-6 col-12 mb-3">
                    <a href="/defaults?action_defaults[mode]={{ App\File::MODE_SCRUB }}&target_action=/files" class="btn btn-warning pull-right">
                        <i class="fa fa-cut"></i>
                        Scrub Against this List
                    </a>
                </div>

                <div class="col-xl-5 col-lg-6 col-12 mb-3">
                    <a href="/defaults?action_defaults[mode]={{ App\File::MODE_LIST_APPEND }}&action_defaults[suppression_list_append]={{ $list->id }}&target_action={{ route('files') }}" class="btn btn-info pull-right">
                        <i class="fa fa-plus"></i>
                        Add to this List
                    </a>
                </div>

                <div class="col-xl-5 col-lg-6 col-12 mb-3">
                    <a href="/defaults?action_defaults[mode]={{ App\File::MODE_LIST_REPLACE }}&action_defaults[suppression_list_replace]={{ $list->id }}&target_action={{ route('files') }}" class="btn btn-primary pull-right">
                        <i class="fa fa-plus"></i>
                        Replace this List
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif
