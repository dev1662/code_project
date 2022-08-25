@component('mail::message')
# Hello {{ $centralUser->name }}

Organization Details:

@if(count($tenants) > 0)
@foreach($tenants as $tenantKey => $tenant)

Organization {{ $tenantKey+1 }}: <b>{{ $tenant->organization->name }} ({{ $tenant->organization->subdomain }})

@endforeach
@endif

Stay connected!
@endcomponent