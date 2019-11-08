window.Dropzone = require('dropzone');

Dropzone.autoDiscover = false;

$(function () {
    var $dropzoneForm = $('form#dropzone:first');
    if ($dropzoneForm.length) {
        var DZ = new Dropzone(document.body, {
            url: $dropzoneForm.attr('action'),
            previewsContainer: '#previews',
            clickable: 'form#dropzone',
            previewTemplate: $('#dropzone-preview-template:first').html(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            autoDiscover: false,
            maxFilesize: 100,
            acceptedFiles: '.xlsx, .csv, .tsv, .ods, .xls, .slk, .xml, .gnumeric, .html',
            addRemoveLinks: false,
            timeout: 899,
            uploadMultiple: true,
            init: function () {
                this.on('successmultiple', function (files, response) {
                    var $filelist = $('#fileslist');
                    if ($filelist.length) {
                        $.each(files, function (index, file) {
                            console.log(response.routes[file.name]);
                            if (typeof response.routes[file.name] !== 'undefined') {
                                // Upload succeeded.
                                var $card = $(file.previewElement);
                                var $clone = $card.clone().css({
                                    'opacity': 0,
                                    'max-height': '0px'
                                }, st.animationSpeed);
                                $filelist.prepend($clone);

                                st.loadContent(response.routes[file.name], $clone, false, function () {
                                    $clone.animate({
                                        'opacity': 1,
                                        'max-height': '600px'
                                    }, st.animationSpeed, function () {
                                        $(this).css({'max-height': 'auto'});
                                    });
                                    st.fileLoaded($clone);
                                });
                                $card
                                    .css({'position': 'relative'})
                                    .animate({
                                        'top': '220px',
                                        'margin-left': '-1em',
                                        'margin-right': '-1em',
                                        'opacity': 0
                                    }, st.animationSpeed, function () {
                                        $(this).animate({
                                            'height': '0px'
                                        }, 600, function () {
                                            $(this).remove();
                                        });
                                    });
                                $('#file-list-header:first.d-none')
                                    .css({'opacity': 0})
                                    .removeClass('d-none')
                                    .animate({
                                        'opacity': 1
                                    }, st.animationSpeed);
                            }
                        });
                        // if (typeof response.routes !== 'undefined') {
                        //     $.each(response.routes, function (fileName,
                        // route) { st.loadContent(route, $filelist, true,
                        // function () { st.fileLoaded($filelist); }); var
                        // $card = $('span.file-preview-name:contains("' +
                        // fileName + '"):first').parent().parent(); // if
                        // (!$card.length) { //     $card =
                        // $(file.previewElement); // } $card .css({'position':
                        // 'relative'}) .animate({ 'top': '220px',
                        // 'margin-left': '-1em', 'margin-right': '-1em',
                        // 'opacity': 0 }, 600, function () { $(this).animate({
                        // 'height': '0px' }, 600, function () {
                        // $(this).remove(); }); });
                        // $('#file-list-header:first.d-none') .css({'opacity':
                        // 0}) .removeClass('d-none') .animate({ 'opacity': 1
                        // }, 600); }); }
                    }
                });
            },
        });
    }
});
