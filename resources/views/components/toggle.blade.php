@props([
'label' => '',
'color' => 'brand',
'checked' => false, // Tambahkan prop untuk status awal
])

@php
$activeColor = match($color) {
'brand' => 'peer-checked:bg-brand',
'red' => 'peer-checked:bg-red-600',
'green' => 'peer-checked:bg-green-500',
default => 'peer-checked:bg-brand',
};
@endphp

<div {{ $attributes->class(['flex items-center gap-3']) }}>
    <label class="relative inline-flex items-center cursor-pointer">
        {{-- Gunakan atribut checked dan pastikan wire:model atau wire:click diteruskan --}}
        <input type="checkbox" {{ $attributes->whereStartsWith('wire:') }}
        class="sr-only peer"
        @if($checked) checked @endif
        >

        {{-- Background Track --}}
        <div class="w-11 h-6 bg-gray-300 rounded-full transition-colors duration-200 {{ $activeColor }}"></div>

        {{-- Bulatan (Handle) - Tetap Putih --}}
        <div
            class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200 peer-checked:translate-x-5">
        </div>
    </label>

    @if($label)
    <span class="text-sm font-medium text-brand-dark">
        {{ $label }}
    </span>
    @endif
</div>