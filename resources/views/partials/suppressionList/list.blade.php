<section id="lists">
    @if(count($suppressionLists))
        <div id="accordion" role="tablist" aria-multiselectable="true">
            @foreach($suppressionLists as $suppressionList)
                @include('partials.suppressionList.item')
            @endforeach
        </div>
    @else
        <p class="text-info">
            {{ __('You currently have no suppression lists.') }}
        </p>
    @endif
    <a href="{{ route('files') }}" class="btn btn-success btn-large pull-right">
        <i class="fa fa-plus"></i> {{ __('Create a Suppression List') }}
    </a>
</section>
