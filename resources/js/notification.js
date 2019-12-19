st.notifications = ($context) => {
    var $nav = $('a#notifications:first', $context),
        $bell = $nav.find('.fa-bell:first'),
        $dropdown = $nav.parent('.nav-item'),
        $template = $dropdown.find('.dropdown-item.d-none:first'),
        $notifications = $dropdown.find('ul:first'),
        unreadCount = $bell.attr('data-count') || 0,
        markAllAsRead = () => {
            if (unreadCount) {
                $bell.removeClass('has-badge')
                    .attr('data-count', '0');
                $.getJSON($nav.attr('href'));
                unreadCount = 0;
            }
        };

    $nav.click(markAllAsRead);
    $dropdown.click(markAllAsRead);

    st.notificationNew = (d) => {
        var $new = $template.clone();
        $new.removeClass('d-none');
        if (typeof d.icon !== 'undefined') {
            $new.find('.fa')
                .removeClass('fa-info')
                .addClass('fa-' + d.icon);
        }
        if (typeof d.url !== 'undefined') {
            $new.attr('href', d.url);
        }
        if (typeof d.message !== 'undefined') {
            unreadCount++;
            $new.find('.message').text(d.message);
            $notifications.prepend($new);
            $bell.addClass('has-badge')
                .attr('data-count', unreadCount);
        }
    };
};

$(function () {
    st.notifications($('body'));
});
