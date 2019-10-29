@section('scripts')
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.4.0/dropzone.js"></script>
    <script type="text/javascript">
        Dropzone.autoDiscover = false;
        $(function() {
            var DZ = new Dropzone(document.body, {
                url: $('form#dropzone:first').attr('action'),
                previewsContainer: '#previews',
                clickable: 'form#dropzone',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                autoDiscover: false,
                maxFilesize: 100,
                acceptedFiles: '.xlsx, .csv, .tsv, .ods, .xls, .slk, .xml, .gnumeric, .html',
                addRemoveLinks: false,
                timeout: 899,
                success: function (file, response) {
                    console.log(file, response);
                }
            });
        });
    </script>
@endsection

@section('styles')
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.4.0/min/dropzone.min.css">
    <style type="text/css">
        form#dropzone {
            border-radius: 30px;
            border: .4rem dashed #616778;
        }

        .dropzone .dz-message {
            text-align: center;
            margin: 2em 0;
            top: 14px;
            position: relative;
            font-weight: bold;
        }
    </style>
@endsection

<div class="container">
    <form method="post" action="{{url('files/upload')}}" enctype="multipart/form-data" class="dropzone" id="dropzone">
        @csrf
        <div class="dz-message">Drop your spreadsheets here or click to begin</div>
        <div id="previews" class="dropzone-previews"></div>
    </form>
</div>
