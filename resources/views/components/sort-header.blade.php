@props([
'field',
'label',
'sortField',
'sortDirection',
'showOnMobile' => false, // Tambahkan prop baru dengan default false
])

@php
$isRight = str_contains($attributes->get('class'), 'text-right');

// Tentukan class responsif berdasarkan prop
// Jika showOnMobile true, gunakan 'table-cell'
// Jika false, gunakan 'hidden md:table-cell'
$mobileClass = $showOnMobile ? 'table-cell' : 'hidden md:table-cell';
@endphp

<th wire:click="sortBy('{{ $field }}')" {{ $attributes->merge(['class' => "px-4 py-3 cursor-pointer select-none
    $mobileClass"]) }}>

    <div class="flex items-center gap-1 group {{ $isRight ? 'justify-end' : '' }}">
        <span>{{ $label }}</span>

        <div class="flex flex-col leading-none text-[11px] gap-1 ml-2">
            <span
                class="transition {{ $sortField === $field && $sortDirection === 'asc' ? 'text-brand-800' : 'text-gray-300 group-hover:text-gray-400' }}">
                ▲
            </span>
            <span
                class="transition -mt-1 {{ $sortField === $field && $sortDirection === 'desc' ? 'text-brand-800' : 'text-gray-300 group-hover:text-gray-400' }}">
                ▼
            </span>
        </div>
    </div>
</th>