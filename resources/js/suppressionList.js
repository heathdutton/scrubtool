import '@github/clipboard-copy-element';

st.suppressionList = function ($context) {
    document.addEventListener('clipboard-copy', function (event) {
        var $t = $(event.target).first();
        $t.tooltip('toggle');
        setTimeout(function () {
            $t.tooltip('hide');
        }, 10000);
    });
};

$(function () {
    st.suppressionList($('body'));
});
