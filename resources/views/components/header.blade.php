@props([
'header',
'description',
])

<div class="text-left mb-6">
    <flux:heading size="xl">{{ $header }}</flux:heading>
    <flux:text class="mb-6 text-base">{{ $description }}</flux:text>
    <flux:separator />
</div>