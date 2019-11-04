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
    };

    st.fileLoaded($('body'));
});
