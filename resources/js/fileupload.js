window.Dropzone = require('dropzone');

Dropzone.autoDiscover = false;

st.dropzone = function ($context) {
    if (!$context.length) {
        return;
    }
    var DZ = new Dropzone(document.body, {
        url: $context.attr('action'),
        previewsContainer: '#previews',
        clickable: 'form#dropzone',
        previewTemplate: $('#dropzone-preview-template:first').html(),
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]:first').attr('content')
        },
        autoDiscover: false,
        maxFilesize: $context.data('max-upload-mb'),
        acceptedFiles: $context.data('accepted-files'),
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
                                var $card = $(file.previewElement),
                                    height = $card.height(),
                                    $destination = $card.clone().css({
                                        'opacity': 0,
                                        'max-height': 0
                                    }, st.animationSpeed);

                                // Put the destination in place even though it
                                // is empty.
                                $filelist.prepend($destination);

                                // Ajax load into the clone while animating.
                                st.fileLoad(response.routes[file.name], $destination);

                                // Animate the preview card down to the
                                // destination.
                                $card
                                    .css({
                                        'position': 'relative'
                                    })
                                    .animate({
                                        'top': ($destination.offset().top - $card.offset().top - height) + 'px',
                                        'margin-left': '-1.5em',
                                        'margin-right': '-1.5em',
                                        'margin-bottom': -height,
                                        'opacity': 0
                                    }, st.animationSpeed, function () {
                                        $(this).remove();
                                    });

                                // Start making room at the destination.
                                $destination.animate({
                                    'max-height': height,
                                    'opacity': 1
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
                    }, 800);
                }
            });
        },
        error: function (file, response, xhr) {
            var $text = $(file.previewElement).find('.text-danger > span:first');
            if (typeof response.errors !== 'undefined' && typeof response.errors[file.name] !== 'undefined') {
                if (typeof console.warn == 'function') {
                    console.warn(file.name + ' - ' + response.errors[file.name]);
                }
                $text.text(response.errors[file.name]);
            }
            else {
                $text.text('An unexpected error occurred. Please try again.');
            }
            $(file.previewElement)
                .addClass('dz-error')
                .addClass('border-danger')
                .removeClass('border-secondary');
            $(file.previewElement)
                .find('.progress:first')
                .slideToggle();
        }
    });
};

$(function () {
    st.dropzone($('form#dropzone:first'));
});
