<section id="lists">
    <h1>{{ __('Your Suppression Lists') }}</h1>
    @if(count($lists))
        <div id="accordion" role="tablist" aria-multiselectable="true">
            @foreach($lists as $list)
                @include('partials.list')
            @endforeach
        </div>
    @else
        <p class="text-info">
            {{ __('You currently have no suppression lists.') }}
        </p>
    @endif
    <a href="{{ route('files') }}" class="btn btn-primary btn-large"><i class="fa fa-plus"></i> {{ __('Create a Suppression List') }}</a>
</section>
