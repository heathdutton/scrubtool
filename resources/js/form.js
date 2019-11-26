st.form = function ($context) {
    $('a.btn, input:submit.btn, input:button.btn', $context)
        .off('click.submit-animate')
        .on('click.submit-animate', function () {
            var $t = $(this);
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
                        'width': $t.outerWidth(),
                        'height': $t.outerHeight(),
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
                    $t.addClass('submit-animate').addClass('submit-animating');
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
                        $t.removeClass('submit-animate').removeClass('submit-animating');
                    });
                }, 1500);
            }
        });
};

$(function () {
    st.form($('body'));
});
