<div class="col-xl-3 col-lg-6 col-12">
    <div class="card @if($class) border-{{ $class }} @endif">
        <div class="card-content">
            <div class="card-body">
                <div class="media d-flex">
                    <div class="align-self-center">
                        <i class="fa fa-{{ $icon }} fa-4x pull-left @if($class) text-{{ $class }} @endif"></i>
                    </div>
                    <div class="media-body text-right">
                        <h3>{{ $value }}</h3>
                        <span>{{ $label }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
