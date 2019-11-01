require('./bootstrap');
require('./fileupload');

$(function () {
    $('[data-toggle="tooltip"]').tooltip();
});

var fileProgress = function (fileId, total = 100, delay = 500) {
    $(function () {
        setTimeout(function () {
            $('#file-' + fileId + ':first .progress-bar:first').css('width', total + '%');
        }, delay);
    });
};
