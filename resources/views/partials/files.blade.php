<section id="fileslist">
    @if(count($files))
        <h1>{{ __('Your Files') }}</h1>
        <div id="accordion" role="tablist" aria-multiselectable="true">
            @foreach($files as $file)
                @include('partials.file')
            @endforeach
        </div>
    @endif
</section>
