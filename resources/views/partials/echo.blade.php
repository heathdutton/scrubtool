@if(config('broadcasting.default') == 'pusher')
    <script defer>
        $(function () {
            if (
                typeof Echo !== 'undefined'
                && window.Pusher !== 'undefined'
            ) {
                window.Echo = new Echo({
                    broadcaster: 'pusher',
                    key: '{{ config('broadcasting.connections.pusher.key') }}',
                    cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
                    client: window.Pusher,
                    forceTLS: true
                });
                var channel = Echo.channel('scrubtool');
                channel.listen('.scrubtool', function (data) {
                    alert(JSON.stringify(data));
                });
            }
        });
    </script>
@endif
