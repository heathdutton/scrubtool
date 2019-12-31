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
                    var $result = $(data.html),
                        $children = $result.find('.card, form');
                    $children.css({
                        'opacity': 0,
                        'max-height': '0px'
                    });
                    $destination.replaceWith($result);
                    $children.animate({
                        'opacity': 1,
                        'max-height': '100px'
                    }, st.animationSpeed, 'swing', function () {
                        $(this).css('max-height', 'auto');
                    });
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
                setTimeout(function () {
                    return done(null, data);
                }, 500);
            }
        }
    });
};
