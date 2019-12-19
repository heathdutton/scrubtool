@component('mail::message')
# {{ $title }}

{{ $message }}

@component('mail::button', ['url' => $url])
{{ $action }}
@endcomponent

{{ $close }},<br>
{{ config('app.name') }}
@endcomponent
