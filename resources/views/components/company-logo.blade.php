@props(['class' => 'h-8'])

@php
    $logo = \App\Models\Setting::get('company_logo');
    $companyName = \App\Models\Setting::get('company_name', config('app.name'));
    $logoUrl = $logo ? \Illuminate\Support\Facades\Storage::disk('public')->url($logo) : null;
@endphp

@if ($logoUrl)
    <img src="{{ $logoUrl }}" alt="{{ $companyName }}" {{ $attributes->merge(['class' => $class . ' w-auto object-contain']) }}>
@else
    <x-application-mark {{ $attributes->merge(['class' => $class]) }} />
@endif
