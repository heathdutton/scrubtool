var myDefaultWhiteList = $.fn.tooltip.Constructor.Default.whiteList;
myDefaultWhiteList.dl = [];
myDefaultWhiteList.dt = [];
myDefaultWhiteList.dd = [];

st.tooltips = function ($context) {
    $('[data-toggle="tooltip"]', $context).tooltip({
        'html': true,
    });
};

$(function() {
    st.tooltips($('body'));
});
