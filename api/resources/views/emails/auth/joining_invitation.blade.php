@component('mail::message')
# Hello {{ $centralUser->name }}

You are invited to join the Organization: {{ $organization->name }}

@component('mail::button', ['url' => $url])
View Invitation
@endcomponent

If you receive any suspicious email/SMS with a link to update your account information, please do NOT open the link-instead, report us for further investigation.

Stay connected!
@endcomponent