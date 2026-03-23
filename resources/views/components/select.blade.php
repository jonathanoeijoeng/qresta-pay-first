@props([
'label' => '',
'name',
])

@php
$model = $attributes->wire('model')->value();
@endphp

<div>
    @if($label)
    <label class="block text-sm font-medium mb-1 text-black dark:text-gray-200">
        {{ $label }}
    </label>
    @endif

    <select {{ $attributes->merge([
        'class' => 'has-[option.placeholder:checked]:text-gray-300
        dark:has-[option.placeholder:checked]:text-gray-200/60
        rounded-lg bg-white w-full
        border rounded px-3 py-2
        focus:outline-none
        focus:ring
        focus:border-brand-400
        dark:bg-zinc-700 dark:border-brand-700 dark:text-white'
        ]) }}
        >
        {{ $slot }}
    </select>

    @error($name)
    <span class="text-sm text-red-500">{{ $message }}</span>
    @enderror
</div>