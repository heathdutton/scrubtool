<section id="fileslist">
    @if(count($files))
        <div id="accordion" role="tablist" aria-multiselectable="true">
            @foreach($files as $file)
                @include('partials.file.item')
            @endforeach
        </div>
    @endif
</section>
