@props([
'field',
'label',
'sortField',
'sortDirection',
])

@php
// Cek apakah ada class text-right di atribut yang dikirim
$isRight = str_contains($attributes->get('class'), 'text-right');
@endphp

<th wire:click="sortBy('{{ $field }}')" {{ $attributes->merge([
    'class' => 'px-4 py-3 cursor-pointer select-none hidden md:table-cell'
    ]) }}>

    {{-- Gunakan justify-end jika text-right dideteksi --}}
    <div class="flex items-center gap-1 group {{ $isRight ? 'justify-end' : '' }}">

        <span>{{ $label }}</span>

        <div class="flex flex-col leading-none text-[11px] gap-1 ml-2">
            {{-- Arrow Up --}}
            <span class="transition
                {{ $sortField === $field && $sortDirection === 'asc'
                    ? 'text-brand-800'
                    : 'text-gray-300 group-hover:text-gray-400' }}">
                ▲
            </span>

            {{-- Arrow Down --}}
            <span class="transition -mt-1
                {{ $sortField === $field && $sortDirection === 'desc'
                    ? 'text-brand-800'
                    : 'text-gray-300 group-hover:text-gray-400' }}">
                ▼
            </span>
        </div>
    </div>
</th>