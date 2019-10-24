@section('scripts')
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.4.0/dropzone.js"></script>
    <script type="text/javascript">
        Dropzone.options.dropzone =
            {
                maxFilesize: 100,
                // renameFile: function (file) {
                //     var dt = new Date();
                //     var time = dt.getTime();
                //     return time + file.name;
                // },
                acceptedFiles: '.xlsx,.csv,.tsv,.ods,.xls,.slk,.xml,.gnumeric,.html',
                addRemoveLinks: false,
                timeout: 600,
                success: function (file, response) {
                    console.log(response);
                },
                error: function (file, response) {
                    return false;
                }
            };
    </script>
@endsection

@section('styles')
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.4.0/min/dropzone.min.css">
@endsection

<div class="container">
    <h3 class="jumbotron">Upload a spreadsheet to hash the contact details</h3>
    <form method="post" action="{{url('hash/file/upload')}}" enctype="multipart/form-data"
          class="dropzone" id="dropzone">
        @csrf
    </form>
</div>
