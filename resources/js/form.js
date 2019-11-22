st.form = function ($context) {
    $('a.btn, input:submit.btn, input:button.btn', $context)
        .off('click.submit-animate')
        .on('click.submit-animate', function () {
            var $t = $(this);
            if (!$t.hasClass('submit-animate')) {
                var $wrap = $('<div class="text-center">')
                    .css({
                        'width': $t.outerWidth(),
                        'height': $t.outerHeight(),
                        'left': $t.position().left,
                        'right': $t.position().right,
                        'margin': '0 auto'
                    });
                $t.wrap($wrap);
                setTimeout(function () {
                    // $icon = $t.find('i:first');
                    // if ($icon.length) {
                    //     var $newIcon = $icon.first().clone().appendTo($wrap);
                    // }
                    $t.addClass('submit-animate');
                }, 10);
            }
            // return false;
        });
};

$(function () {
    st.form($('body'));
});
