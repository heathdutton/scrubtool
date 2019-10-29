@section('scripts')
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.4.0/dropzone.js"></script>
    <script type="text/javascript">
        Dropzone.options.dropzone = {
            autoDiscover: false,
            maxFilesize: 100,
            acceptedFiles: '.xlsx,.csv,.tsv,.ods,.xls,.slk,.xml,.gnumeric,.html',
            addRemoveLinks: false,
            timeout: 899,
            dictDefaultMessage: '<strong>Drop spreadsheets here or click to upload.</strong>',
            success: function (file, response) {
                console.log(response);
            },
            error: function (file, response) {
                return false;
            }
        };

        $(document).ready(function () {
            var DZ = new Dropzone(document.body, {
                url: "{{url('files/upload')}}",
                previewsContainer: '#previews',
                clickable: '#previews',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        });
    </script>
@endsection

@section('styles')
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.4.0/min/dropzone.min.css">
@endsection

<div class="container">
    <form method="post" action="{{url('files/upload')}}" enctype="multipart/form-data" class="dropzone" id="dropzone">
        @csrf
        <div id="previews" class="dropzone-previews"></div>
    </form>
</div>
