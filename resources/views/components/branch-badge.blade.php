@props([
'color',
'code',
'name'
])

@php
$color = $color ?? '#71717a';
$code = $code ?? '??';
$name = $name;
@endphp

<div class="flex items-center gap-2">
    {{-- Versi Ringkas (Hanya Kode) --}}
    <span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black
        tracking-tighter border"]) }}
        style="background-color: {{ $color }}; color: white; border-color: {{ $color }};"
        title="{{ $name }}">
        {{ strtoupper($code) }}
    </span>

    {{-- Nama Cabang (Opsional, bisa disembunyikan di mobile) --}}
    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400 hidden sm:inline">
        {{ $name }}
    </span>
</div>