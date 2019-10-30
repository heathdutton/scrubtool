<section id="fileslist">
    <div id="accordion" role="tablist" aria-multiselectable="true">
        @foreach($files as $file)
            @include('partials.file')
        @endforeach
    </div>
</section>
