window.st = {};
require('./bootstrap');
require('./fileupload');
require('./tooltips');
require('./file');

st.animationSpeed = '900';

st.loadContent = function (url, $destination, prepend, callback) {
    $.getJSON(url, function (data) {
        if (typeof data.success !== 'undefined' && data.html.length) {
            $result = $(data.html);
            if (prepend) {
                $destination.prepend($result);
            }
            else {
                $destination.replaceWith($result);
            }
            if (typeof callback == 'function') {
                callback($result);
            }
        }
    });
};
