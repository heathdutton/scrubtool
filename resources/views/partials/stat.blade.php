<div id="stat-{{ $id }}-wrapper" class="col-xl-3 col-lg-6 col-12 mb-3 @if(!$value) d-none @endif">
    <div class="card @if($class) border-{{ $class }} @endif">
        <div class="card-content">
            <div class="card-body">
                <div class="media d-flex">
                    <div class="align-self-center">
                        <i class="fa fa-{{ $icon }} fa-4x pull-left {{ $class }}"></i>
                    </div>
                    <div class="media-body text-right">
                        <h3 id="stat-{{ $id }}">{{ $value }}</h3>
                        <span>{{ $label }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
