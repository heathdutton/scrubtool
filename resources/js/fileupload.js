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
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]:first').attr('content')
            },
            autoDiscover: false,
            maxFilesize: $dropzoneForm.data('max-upload-mb'),
            acceptedFiles: $dropzoneForm.data('accepted-files'),
            addRemoveLinks: false,
            timeout: 0,
            uploadMultiple: true,
            init: function () {
                this.on('successmultiple', function (files, response) {
                    var $filelist = $('#fileslist');
                    if ($filelist.length) {
                        // Delay to allow time for file to be secured.
                        setTimeout(function () {
                            $.each(files, function (index, file) {
                                if (typeof response.routes[file.name] !== 'undefined') {
                                    // Upload succeeded.

                                    // Clone the file card in the lower content
                                    var $card = $(file.previewElement);
                                    var $destination = $card.clone().css({
                                        'opacity': 0,
                                        'max-height': '0px'
                                    }, st.animationSpeed);
                                    $filelist.prepend($destination);

                                    // Ajax load into the clone while animating.
                                    st.fileLoad(response.routes[file.name], $destination);

                                    // Animate the preview card down to the
                                    // destination.
                                    $card
                                        .css({'position': 'relative'})
                                        .animate({
                                            'top': '220px',
                                            'margin-left': '-1.5em',
                                            'margin-right': '-1.5em',
                                            'opacity': 0
                                        }, st.animationSpeed, function () {
                                            $(this).animate({
                                                'height': '0px'
                                            }, st.animationSpeed, function () {
                                                $(this).remove();
                                            });
                                        });

                                    // Fade in new card during hte ajax req.
                                    $destination.animate({
                                        'opacity': 1,
                                        'max-height': '600px'
                                    }, st.animationSpeed, function () {
                                        $(this).css({'max-height': 'auto'});
                                    });

                                    // Show the "files" header
                                    $('#file-list-header:first.d-none')
                                        .css({'opacity': 0})
                                        .removeClass('d-none')
                                        .animate({
                                            'opacity': 1
                                        }, st.animationSpeed);
                                }
                            });
                        }, 1000);
                    }
                });
            },
        });
    }
});
