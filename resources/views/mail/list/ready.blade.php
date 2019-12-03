@component('mail::message')
# Your Suppression List is Ready

Your suppression list has been fully uploaded and is ready for use.

@component('mail::button', ['url' => route('suppressionList', ['id' => $id])])
Open Suppression List
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
