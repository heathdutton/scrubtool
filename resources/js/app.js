window.st = {};
require('./bootstrap');
require('./fileupload');
require('./tooltips');
require('./file');

st.animationSpeed = 'slow';
st.loadContent = function (url, $destination, prepend, callback) {
    $.getJSON(url, function (data) {
        if (typeof data.success !== 'undefined' && data.html.length) {
            if (prepend) {
                $destination.prepend($(data.html));
            }
            else {
                $destination.replaceWith(data.html);
            }
            if (typeof callback == 'function') {
                callback();
            }
        }
    });
};
