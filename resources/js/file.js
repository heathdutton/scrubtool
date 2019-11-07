jQuery.countdown = require('jquery.countdown');

$(function () {
    st.fileLoaded = function ($context) {
        $('time.countdown', $context)
            .countDown({
                'always_show_hours': false,
                'with_hh_leading_zero': false,
                'with_mm_leading_zero': false,
                'with_ss_leading_zero': false,
                'separator': ' ',
                'with_separators': true,
                'label_dd': 'd',
                'label_hh': 'h',
                'label_mm': 'm',
                'label_ss': 's',
            })
            .animate({'opacity': 1}, 200);
        var $hrs = $('time.countdown', $context).find('.item.item-hh');
        if ($hrs.length && $hrs.text() === '0h') {
            $hrs.hide();
        }
        var $min = $('time.countdown', $context).find('.item.item-mm');
        if ($min.length && $min.text() === '0m') {
            $min.hide();
        }
        $(':checkbox[name=show_all]', $context).bind('change', function () {
            $(this).parent().parent().find('.column-empty').toggle();
        });
    };
    st.fileLoaded($('body'));
});
