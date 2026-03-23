@props([
'showOnMobile' => false, // Tambahkan prop baru dengan default false
])

@php
$mobileClass = $showOnMobile ? 'table-cell' : 'hidden md:table-cell';
@endphp

<td {{ $attributes->merge(['class' => "px-4 py-3 whitespace-nowrap $mobileClass"]) }}>
    {{ $slot }}
</td>