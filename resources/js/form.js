st.form = function ($context) {
    $('a.btn, input:submit.btn, input:button.btn', $context)
        .off('click.submit-animate')
        .on('click.submit-animate', function () {
            var $t = $(this),
                h = $t.outerHeight(),
                w = $t.outerWidth();
            if (!$t.hasClass('submit-animate')) {
                var $overlay = $('<div>').css({
                    'display': 'block',
                    'position': 'fixed',
                    'top': 0,
                    'left': 0,
                    'width': '100%',
                    'height': '100%',
                    'opacity': 0,
                    'background': '#fff',
                });
                var $wrap = $('<div class="text-center">')
                    .css({
                        'width': w,
                        'height': h,
                        'left': $t.position().left,
                        'right': $t.position().right,
                        'margin': '0 auto'
                    });
                $('body').append($overlay);
                $t.wrap($wrap);
                setTimeout(function () {
                    // $icon = $t.find('i:first');
                    // if ($icon.length) {
                    //     var $newIcon = $icon.first().clone().appendTo($wrap);
                    // }
                    $t.addClass('submit-animate')
                        .animate({
                            'height': h,
                            'width': h
                        }, 100)
                        .addClass('submit-animating');
                }, 10);
                $overlay.animate({
                    'opacity': .3
                }, 300);
                setTimeout(function () {
                    $overlay.animate({
                        'opacity': 0
                    }, 300, function () {
                        $overlay.remove();
                        $t.unwrap($wrap);
                        $t.removeClass('submit-animate')
                            .animate({
                                'height': h,
                                'width': w
                            }, 100)
                            .removeClass('submit-animating');
                    });
                }, 2000);
            }
        });
};

$(function () {
    st.form($('body'));
});
