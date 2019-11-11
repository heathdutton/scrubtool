jQuery.countdown = require('jquery.countdown');

st.classModePrefix = '.file-mode';
st.refreshDelay = 1000;
st.filesLoaded = function ($context) {
    // Hide fields irrelevant to the current file mode.
    $('select[name=mode]', $context).bind('change', function () {
        var $form = $(this).closest('form');
        var val = $(this).val();
        $form.find(st.classModePrefix + ':not(' + st.classModePrefix + '-' + val + ')').hide();
        $form.find(st.classModePrefix + '-' + val).show();
    }).trigger('change');

    // Show all columns switch.
    $(':checkbox[name=show_all]', $context).bind('change', function () {
        $(this).parent().parent().find('.column-empty').toggle();
    });

    // Countdown timer till download is not available.
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

    st.filesRefresh($context);
};

st.fileLoad = function ($route, $destination) {
    st.loadContent($route, $destination, false, function ($context) {
        st.filesLoaded($context);
    });
};

st.fileRefresh = function ($file, delay) {
    setTimeout(function () {
        if ($file.hasClass('file-refresh')) {
            st.fileLoad($file.attr('data-file-origin'), $file);
        }
    }, delay);
};

st.filesRefresh = function ($context) {
    var selector = '.file-refresh';
    var $files = $(selector, $context);
    if (!$files.length && $context.is(selector)) {
        $files = $context;
    }
    if ($files.length) {
        $files.each(function () {
            st.fileRefresh($(this), st.refreshDelay);
        });
    }
};

$(function () {
    st.filesLoaded($('body'));
});
