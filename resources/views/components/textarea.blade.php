@props([
'label' => '',
'name',
'type' => 'text',
'rows' => '3',
])

<div>
    @if($label)
    <label class="block text-sm font-medium mb-1 text-black dark:text-gray-200">
        {{ $label }}
    </label>
    @endif

    <textarea rows="{{ $rows }}" type="{{ $type }}" {{ $attributes->merge([
    'class' => 'w-full border rounded-lg shadow shadow-zinc-200 dark:shadow-zinc-900 px-3 py-2 focus:outline-none
    focus:ring
    focus:border-blue-400
    bg-white
    dark:bg-zinc-800 dark:border-[#3E3E3A] dark:text-white'
    ]) }}
    ></textarea>

    @error($name)
    <span class="text-sm text-red-500">{{ $message }}</span>
    @enderror
</div>