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
                @include('partials.stat', ['icon' => 'database', 'class' => '', 'value' => $list->rows_total ?? 0, 'label' => __('Total Records')])
            </div>
        </div>
    </div>
@endif
