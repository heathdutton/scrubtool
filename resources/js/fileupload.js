window.Dropzone = require('dropzone');

Dropzone.autoDiscover = false;

$(function () {
    var $dropzoneForm = $('form#dropzone:first');
    if ($dropzoneForm.length) {
        var DZ = new Dropzone(document.body, {
            url: $dropzoneForm.attr('action'),
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
    }
});
