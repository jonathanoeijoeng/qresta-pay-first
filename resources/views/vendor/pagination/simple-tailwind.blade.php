@if ($paginator->hasPages())
<div class="flex md:justify-between justify-center items-center">
    <div class="hidden md:block">
        <p class="text-sm text-gray-700 leading-5 dark:text-gray-600">
            {!! __('Showing') !!}
            @if ($paginator->firstItem())
            <span class="font-medium">{{ $paginator->firstItem() }}</span>
            {!! __('to') !!}
            <span class="font-medium">{{ $paginator->lastItem() }}</span>
            @else
            {{ $paginator->count() }}
            @endif
            {!! __('of') !!}
            <span class="font-medium">{{ $paginator->total() }}</span>
            {!! __('results') !!}
        </p>
    </div>
    <div>
        <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
            class="flex gap-2 items-center justify-between">

            {{-- Tombol Previous --}}
            @if ($paginator->onFirstPage())
            <span
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-200 cursor-not-allowed leading-5 rounded-md dark:text-gray-500 dark:bg-gray-800 dark:border-gray-700">
                {!! __('pagination.previous') !!}
            </span>
            @else
            {{-- Ganti <a> ke <button> dan tambahkan wire:click --}}
                    <button type="button" wire:click="previousPage" wire:loading.attr="disabled"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 leading-5 rounded-md hover:bg-gray-50 focus:outline-none transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        {!! __('pagination.previous') !!}
                    </button>
                    @endif

                    {{-- Tombol Next --}}
                    @if ($paginator->hasMorePages())
                    {{-- Ganti <a> ke <button> dan tambahkan wire:click --}}
                            <button type="button" wire:click="nextPage" wire:loading.attr="disabled"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 leading-5 rounded-md hover:bg-gray-50 focus:outline-none transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                {!! __('pagination.next') !!}
                            </button>
                            @else
                            <span
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-200 cursor-not-allowed leading-5 rounded-md dark:text-gray-500 dark:bg-gray-800 dark:border-gray-700">
                                {!! __('pagination.next') !!}
                            </span>
                            @endif
        </nav>
    </div>
</div>
@endif