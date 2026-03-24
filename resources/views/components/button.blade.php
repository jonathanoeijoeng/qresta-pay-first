@props([
'variant' => 'brand', // Default warna
'type' => 'button',
'loading' => null, // Target wire:loading
'icon' => null // Nama komponen icon (opsional)
])

@php
// Mapping warna untuk background dan hover
$variants = [
'blue' => 'bg-blue-600 hover:bg-blue-800 text-white shadow-blue-200 dark:shadow-none',
'green' => 'bg-emerald-600 hover:bg-emerald-800 text-white shadow-emerald-200 dark:shadow-none',
'orange' => 'bg-orange-500 hover:bg-orange-700 text-white shadow-orange-200 dark:shadow-none',
'red' => 'bg-red-600 hover:bg-red-800 text-white shadow-red-200 dark:shadow-none',
'zinc' => 'bg-zinc-800 hover:bg-black text-white dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200',
'brand' => 'bg-brand-500 hover:bg-brand-700 text-white shadow-brand/50 dark:shadow-none',
];

$selectedVariant = $variants[$variant] ?? $variants['blue'];
@endphp

<button {{ $type==='submit' ? '' : 'type=button' }} {{ $attributes->merge([
    'class' => "cursor-pointer inline-flex items-center justify-center px-4 py-2 rounded-lg transition-all
    duration-200 shadow hover:shadow active:scale-95 disabled:opacity-50 disabled:pointer-events-none " .
    $selectedVariant
    ]) }}
    @if($loading) wire:loading.attr="disabled" @endif
    >
    {{-- Spinner Loading --}}
    @if($loading)
    <svg wire:loading wire:target="{{ $loading }}" class="animate-spin -ml-1 mr-2 h-4 w-4 text-current"
        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
        </path>
    </svg>
    @endif

    {{-- Icon (Jika ada) --}}
    @if($icon && !$loading)
    <span class="mr-2">
        {{-- Anda bisa memanggil komponen icon Anda di sini --}}
        {{--
        <x-dynamic-component :component="$icon" class="w-4 h-4" /> --}}
    </span>
    @endif

    {{-- Label Button --}}
    <span @if($loading) wire:loading.remove wire:target="{{ $loading }}" @endif>
        {{ $slot }}
    </span>

    {{-- Label saat Loading --}}
    @if($loading)
    <span wire:loading wire:target="{{ $loading }}">
        Processing...
    </span>
    @endif
</button>