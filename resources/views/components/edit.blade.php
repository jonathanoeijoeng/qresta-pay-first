@props([
'id' => null,
'route' => null,
'disabled' => false, // Tambahkan prop disabled
])

@php
$tag = $route ? 'a' : 'button';
$extraAttributes = $route ? ['href' => $route] : ['type' => 'button'];

// Jika disabled, tambahkan atribut disabled ke array
if ($disabled) {
$extraAttributes['disabled'] = 'disabled';
}
@endphp

<{{ $tag }} {{ $attributes->merge(array_merge($extraAttributes, [
    'class' => 'w-6 transform inline-block group focus:outline-none ' . ($disabled ? 'opacity-30 cursor-not-allowed
    grayscale' : '')
    ])) }}>
    <svg class="text-yellow-500 fill-current {{ !$disabled ? 'group-hover:scale-125 cursor-pointer' : '' }} transition duration-200"
        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 500">
        <path
            d="M484.685 196.177c-4-3.8-10.3-3.7-14.1.3l-181 189.6-14.1-57.6 188.8-189.7c3.9-3.9 3.9-10.2 0-14.1-3.9-3.9-10.2-3.9-14.1 0l-188.8 189.6-70.3-17.7-17.7-70.3 189.6-188.8c3.9-3.9 3.9-10.2 0-14.1-3.9-3.9-10.2-3.9-14.1 0l-189.6 188.9-57.6-14.1 189.6-181c4-3.8 4.1-10.1.3-14.1s-10.1-4.1-14.1-.3l-202.7 193.6-.1.1c-.3.2-.5.5-.7.8l-.2.2c-.3.3-.5.7-.7 1v.1c-.2.3-.4.7-.5 1 0 .1-.1.2-.1.3-.1.3-.2.6-.3 1 0 .1 0 .1-.1.2l-71.6 274.4c-.9 3.4.1 7.1 2.6 9.6 1.9 1.9 4.5 2.9 7.1 2.9.8 0 1.7-.1 2.5-.3l274.4-71.6c.1 0 .1 0 .2-.1.3-.1.6-.2 1-.3.1 0 .2-.1.3-.1.3-.2.7-.3 1-.5h.1c.4-.2.7-.5 1-.7l.2-.2c.3-.2.5-.5.8-.7l.1-.1 193.6-202.7c3.4-4.4 3.3-10.7-.7-14.5z" />
    </svg>
</{{ $tag }}>