require('./bootstrap');
require('./fileupload');

var myDefaultWhiteList = $.fn.tooltip.Constructor.Default.whiteList
myDefaultWhiteList.dl = [];
myDefaultWhiteList.dt = [];
myDefaultWhiteList.dd = [];

$('[data-toggle="tooltip"]').tooltip({
    'html': true,
});
