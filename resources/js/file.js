jQuery.countdown = require('jquery.countdown');
st.numberFormat = require('locutus/php/strings/number_format');

st.classModePrefix = 'file-mode';
st.classColumnEmpty = 'column-empty';
st.classHiddenSuffix = '-hidden';
st.modeScrub = 16;
st.refreshDelay = 2000;

st.filesLoaded = function ($context) {
    // Hide fields irrelevant to the current file mode.
    $('select[name=mode]', $context).bind('change', function () {
        var $form = $(this).closest('form');
        var val = $(this).val();
        $form.find('.' + st.classModePrefix + ':not(.' + st.classModePrefix + '-' + val + ')').addClass(st.classModePrefix + st.classHiddenSuffix);
        $form.find('.' + st.classModePrefix + '-' + val).removeClass(st.classModePrefix + st.classHiddenSuffix);
        $form.find('button:submit:first');
        st.fileScrubCoverageCheck($context);
    }).trigger('change');

    $('.column-type > select, .column-hash-input > select', $context).change(function () {
        $(this).closest('.card').each(function () {
            st.fileScrubCoverageCheck($(this));
        });
    });

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

st.fileScrubCoverageCheck = function ($context) {
    $('form', $context).each(function () {
        var $form = $(this),
            $mode = $form.find('select[name=mode]:first'),
            $columns = $form.find('.static-columns:first');

        if (!$mode.length) {
            return;
        }

        if (parseInt($mode.val()) & st.modeScrub) {

            // Discern if a column type [and hash] is supported.
            var columnSupported = function (columnType) {
                    for (var listId in supports) {
                        if (supports.hasOwnProperty(listId)) {
                            if (typeof supports[listId][columnType] !== 'undefined') {
                                return true;
                            }
                        }
                    }
                    return false;
                },
                columnHashSupported = function (columnType, hashType) {
                    for (var listId in supports) {
                        if (supports.hasOwnProperty(listId)) {
                            if (typeof supports[listId][columnType] !== 'undefined') {
                                if (
                                    null === hashType
                                    || supports[listId][columnType].indexOf(hashType)
                                ) {
                                    return true;
                                }
                                return false;
                            }
                        }
                    }
                    return false;
                },
                columnHashesSupported = function (columnType) {
                    var algos = {};
                    for (var listId in supports) {
                        if (supports.hasOwnProperty(listId)) {
                            if (typeof supports[listId][columnType] !== 'undefined') {
                                for (var algo in supports[listId][columnType]) {
                                    if (supports[listId][columnType].hasOwnProperty(algo) && null !== supports[listId][columnType][algo]) {
                                        algos[supports[listId][columnType][algo]] = true;
                                    }
                                }
                            }
                        }
                    }
                    return Object.keys(algos).join(', ').toUpperCase();
                },
                errorAdd = function (message, $target, $form, replace = false) {
                    $form.bind('submit.halt', function (e) {
                        e.preventDefault();
                        return false;
                    });
                    var search = message.replace(/(<([^>]+)>)/ig, ''),
                        $error = $target.find('.invalid-feedback.dynamic:contains(\'' + search + '\')').first();
                    if ($error.length && replace) {
                        $error.html(message);
                    }
                    else {
                        if (!$error.length) {
                            $error = $('<div class="invalid-feedback dynamic text-danger ml-4 mt-2 mb-4">' + message + '</div>');
                            $target.append($error);
                        }
                    }
                    $error.addClass('d-block');
                    $form.find('button.file-mode.file-mode-' + st.modeScrub + ':submit:first').attr('disabled', 'disabled');
                },
                errorClearAll = function ($form) {
                    $form.find('button.file-mode.file-mode-' + st.modeScrub + ':submit:first').attr('disabled', null);
                    $form.unbind('submit.halt');
                    $form.find('.invalid-feedback.dynamic').removeClass('d-block');
                };

            // Discern supports available given selected suppression lists.
            var supports = [];
            $form.find('div.file-mode.file-mode-' + st.modeScrub).each(function () {
                var listId = $(this).find('input:checked:first').val();
                if (listId) {
                    supports[listId] = JSON.parse($(this).attr('data-supports'));
                }
            });

            // Validate selected column types [and input hash types]
            var supportedColumnTypeFound = false,
                unsupportedHashFound = false;

            $form.find('.column-type > select').each(function () {
                var $columnType = $(this),
                    columnTypeName = $columnType.attr('name'),
                    columnType = $columnType.val(),
                    hashTypeName = columnTypeName.replace('column_type_', 'column_hash_input_'),
                    $hashType = $form.find('.column-hash-input > select[name=' + hashTypeName + ']:first'),
                    hashType = $hashType.val() || null;

                if (columnSupported(columnType)) {
                    supportedColumnTypeFound = true;
                    if ($hashType && hashType) {
                        if (columnHashSupported(columnType, hashType)) {
                            $hashType.removeClass('is-invalid');
                        }
                        else {
                            $hashType.addClass('is-invalid');
                            unsupportedHashFound = true;
                            var message = $columns.attr('data-error-column-input-hash');
                            if (message.length) {
                                errorAdd(message.replace(':algos', columnHashesSupported(columnType)), $hashType.parent(), $form, true);
                            }
                        }
                    }
                }
            });

            if (!supportedColumnTypeFound || unsupportedHashFound) {
                if (!supportedColumnTypeFound) {
                    for (var listId in supports) {
                        if (supports.hasOwnProperty(listId)) {
                            for (var colId in supports[listId]) {
                                errorAdd($columns.attr('data-error-column-type-' + colId), $columns, $form);
                            }
                        }
                    }
                }
            }
            else {
                errorClearAll($form);
            }
        }
        else {
            errorClearAll($form);
        }
    });

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
