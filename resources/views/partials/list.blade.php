<?php
$class  = 'secondary';
$action = '';
?>
@if(isset($list->id))
    <div class="card border-{{ $class }} mb-3">

        <a href="#list{{ $list->id }}" class="card-header text-{{ $class }}" role="tab" id="heading{{ $list->id }}" data-toggle="collapse" data-parent="#accordion" aria-expanded="true" aria-controls="list{{ $list->id }}">
            {{ $list->name }}
            <i class="fa fa-chevron-down pull-right"></i>
        </a>

        <div id="list{{ $list->id }}" class="collapse show" role="tabpanel" aria-labelledby="heading{{ $list->id }}">
            <div class="card-body">
                <p class="card-text">List description to come</p>
            </div>
        </div>

        <form>
            @csrf
        </form>
    </div>
@endif
