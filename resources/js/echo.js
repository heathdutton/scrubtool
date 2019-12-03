/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */
window.Pusher = require('pusher-js');

import Echo from 'laravel-echo';

st.echoStart = function () {
    var key = $('meta[name="pusher-key"]:first').attr('content');
    if (key.length) {
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: key,
            cluster: $('meta[name="pusher-cluster"]:first').attr('content') // ,
            // encrypted: true
        });
        var userId = $('meta[name="user-id"]:first').attr('content');
        if (userId.length) {
            var privateChannel = 'users.' + userId;
            window.Echo.channel(privateChannel)
                .listen('list.ready', function (data) {
                    alert(JSON.stringify(data));
                });
            console.log('listening to '+ privateChannel);
        }
        // var channel = Echo.channel('scrubtool');
        // channel.listen('.scrubtool', function (data) {
        //     alert(JSON.stringify(data));
        // });
    }
};

$(function () {
    st.echoStart();
});
