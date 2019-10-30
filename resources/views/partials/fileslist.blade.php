<section id="fileslist">
    <div class="card">
        @foreach($files as $file)
            @include('partials.file')
        @endforeach
    </div>
</section>
