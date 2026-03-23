@props(['headers', 'collection' => null])

<div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
    <table {{ $attributes->merge(['class' => 'w-full text-sm min-w-full border-collapse']) }}>
        <thead class="bg-brand-500 dark:bg-brand-900 text-white text-sm font-semibold uppercase tracking-wider">
            <tr>
                {{ $headers }}
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
            {{ $slot }}
        </tbody>
    </table>
</div>
{{-- Pagination Area --}}
@if($collection && $collection->hasPages())
<div class="px-4 py-3  mt-1">
    {{ $collection->links('vendor.pagination.simple-tailwind') }}
</div>
@endif