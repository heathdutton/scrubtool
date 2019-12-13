st.loadContent = function (url, $destination, prepend, done) {
    $.getJSON(url, function (data) {
        if (
            typeof data.success !== 'undefined'
            && data.success
            && typeof data.html !== 'undefined'
            && data.html.length
        ) {
            if (prepend) {
                var $result = $(data.html);
                $destination.prepend($result);
                if (typeof done == 'function') {
                    return done($result, data);
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
                        return done($result, data);
                    }
                }
            }
            if (typeof done == 'function') {
                return done($destination, data);
            }
        }
        else {
            if (typeof done == 'function') {
                return done(null, data);
            }
        }
    });
};
