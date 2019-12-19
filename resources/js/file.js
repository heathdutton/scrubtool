jQuery.countdown = require('jquery.countdown');
st.numberFormat = require('locutus/php/strings/number_format');

st.classModePrefix = 'file-mode';
st.classColumnEmpty = 'column-empty';
st.classHiddenSuffix = '-hidden';
st.refreshDelay = 2000;

st.filesLoaded = function ($context) {
    // Hide fields irrelevant to the current file mode.
    $('select[name=mode]', $context).bind('change', function () {
        var $form = $(this).closest('form');
        var val = $(this).val();
        $form.find('.' + st.classModePrefix + ':not(.' + st.classModePrefix + '-' + val + ')').addClass(st.classModePrefix + st.classHiddenSuffix);
        $form.find('.' + st.classModePrefix + '-' + val).removeClass(st.classModePrefix + st.classHiddenSuffix);
        $form.find('button:submit:first');
    }).trigger('change');

    // Show all columns switch.
    $(':checkbox[name=show_all]', $context).bind('change', function () {
        var $form = $(this).closest('form');
        if ($(this).is(':checked')) {
            $form.find('.' + st.classColumnEmpty).removeClass(st.classColumnEmpty + st.classHiddenSuffix);
        }
        else {
            $form.find('.' + st.classColumnEmpty).addClass(st.classColumnEmpty + st.classHiddenSuffix);
        }
    });

    // Countdown timer till download is not available.
    $('time.countdown', $context)
        .each(function () {
            st.fileCountDown($(this));
        });

    st.filesRefresh($context);
    st.tooltips($context);
    st.form($context);
};

st.fileCountDown = function ($element, datetime) {
    if (typeof datetime !== 'undefined') {
        $element.data('plugin_countDown').endDate = new Date(datetime);
    }
    else {
        $element.countDown({
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
        });
        var $hrs = $(this).parent().find('.item.item-hh');
        if ($hrs.length && $hrs.text() === '0h') {
            $hrs.hide();
        }
        var $min = $(this).parent().find('.item.item-mm');
        if ($min.length && $min.text() === '0m') {
            $min.hide();
        }
    }
    $element.css('opacity', 1);
};

st.fileLoad = function ($route, $destination) {
    st.loadContent($route, $destination, false, function ($context, data) {
        if ($context) {
            st.filesLoaded($context);
        }
        else {
            if (typeof data.stats !== 'undefined') {
                for (const stat in data.stats) {
                    if (
                        data.stats.hasOwnProperty(stat)
                        && data.stats[stat] > 0
                    ) {
                        var $wrapper = $destination
                            .find('#stat-' + stat + '-wrapper');
                        if ($wrapper) {
                            var $stat = $wrapper.find('#stat-' + stat),
                                vala = parseInt($stat.text().replace(/,/g, '')),
                                valb = data.stats[stat];
                            if ($wrapper.hasClass('d-none')) {
                                $wrapper
                                    .css('opacity', 0)
                                    .removeClass('d-none')
                                    .animate({opacity: 1}, st.animationSpeed);
                            }
                            if (vala !== valb) {
                                $stat
                                    .stop()
                                    .animate({
                                        'val': valb,
                                    }, {
                                        duration: st.refreshDelay * 1.3,
                                        easing: 'linear',
                                        queue: false,
                                        step: function (now, fx) {
                                            if (!fx.start) {
                                                fx.start = vala;
                                            }
                                            fx.elem.innerHTML = st.numberFormat(now);
                                        }
                                    });
                            }
                        }
                    }
                }
            }
            if (typeof data.progress !== 'undefined') {
                $destination.find('.progress-bar:first')
                    .addClass('jquery')
                    .animate({'width': data.progress + '%'}, {
                        queue: false,
                        easing: 'linear',
                        duration: st.refreshDelay * 1.3
                    });
            }
            if (typeof data.eta !== 'undefined') {
                st.fileCountDown($destination.find('.eta:first'), data.eta);
            }
            st.filesRefresh($destination);
        }
    });
};

st.fileRefresh = function ($file, delay) {
    setTimeout(function () {
        if ($file.hasClass('file-refresh')) {
            st.fileLoad($file.attr('data-file-origin') + '/' + $file.attr('data-file-status'), $file);
        }
    }, delay);
};

st.filesRefresh = function ($context) {
    var selector = '.file-refresh',
        $files = $(selector, $context);
    if (!$files.length && $context.is(selector)) {
        $files = $context;
    }
    if ($files.length) {
        $files.each(function () {
            if ($(this).isVisible()) {
                st.fileRefresh($(this), st.refreshDelay);
            }
            else {
                setTimeout(function () {
                    st.filesRefresh($context);
                }, st.refreshDelay / 2);
            }
        });
    }
};

$.fn.isVisible = function () {
    if (!$(this).is(':visible')) {
        return false;
    }
    var elementTop = $(this).offset().top,
        elementBottom = elementTop + $(this).outerHeight(),
        viewportTop = $(window).scrollTop(),
        viewportBottom = viewportTop + $(window).height();
    return elementBottom > viewportTop && elementTop < viewportBottom;
};

$(function () {
    st.filesLoaded($('body'));
});
