<div id="stat-{{ $id ?? 'na' }}-wrapper" class="col-xl-3 col-lg-6 col-12 mb-3 @if(empty($value)) d-none @endif">
    <div class="card border-{{ $class ?? 'secondary' }}">
        <div class="card-content">
            <div class="card-body">
                <div class="media d-flex">
                    <div class="align-self-center">
                        <i class="fa fa-{{ $icon ?? 'info' }} fa-4x pull-left {{ $class ?? 'secondary' }}"></i>
                    </div>
                    <div class="media-body text-right">
                        <h4 id="stat-{{ $id ?? 'na' }}">{{ $value ?? '' }}</h4>
                        <span>{{ $label ?? '' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
