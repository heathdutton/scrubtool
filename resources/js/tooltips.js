var myDefaultWhiteList = $.fn.tooltip.Constructor.Default.whiteList;
myDefaultWhiteList.dl = [];
myDefaultWhiteList.dt = [];
myDefaultWhiteList.dd = [];
$(function() {
    $('[data-toggle="tooltip"]').tooltip({
        'html': true,
    });
});
