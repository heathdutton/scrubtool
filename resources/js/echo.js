/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */
import Echo from "laravel-echo"

window.Pusher = require('pusher-js');

st.echoStart = function () {
    var key = $('meta[name="pusher-key"]:first').attr('content'),
        cluster = $('meta[name="pusher-cluster"]:first').attr('content'),
        userId = $('meta[name="user-id"]:first').attr('content');
    if (key.length && cluster.length && userId.length) {
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: key,
            cluster: cluster,
            encrypted: true
        });
        window.Echo.private('App.Models.User.' + userId)
            .notification((n) => {
                console.log(n);
            });
    }
};

$(function () {
    st.echoStart();
});
