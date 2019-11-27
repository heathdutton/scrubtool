st.loadContent = function (url, $destination, prepend, done) {
    $.getJSON(url, function (data) {
        if (typeof data.success !== 'undefined' && data.html.length) {
            if (prepend) {
                var $result = $(data.html);
                $destination.prepend($result);
                if (typeof done == 'function') {
                    return done($result);
                }
            }
            else {
                if (
                    $destination.data('updated-at') !== data.updated_at
                    && $destination.get(0).outerHTML !== data.html
                ) {
                    var $result = $(data.html);

                    $destination.replaceWith($result);
                    if (typeof done == 'function') {
                        return done($result);
                    }
                }
            }
            if (typeof done == 'function') {
                return done($destination);
            }
        }
    });
};
