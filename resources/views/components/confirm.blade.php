@props([
'model',
'title' => 'Confirm Action',
'message' => 'Are you sure?',
'confirmText' => 'Confirm',
'cancelText' => 'Cancel',
'action',
])

<div x-data="{ open: @entangle($attributes->wire('model')) }" x-show="open" x-on:keydown.escape.window="open = false"
    class="fixed inset-0 z-50 flex items-center justify-center" x-cloak>



    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" x-transition.opacity></div>

    {{-- Modal --}}
    <div x-effect="document.body.classList.toggle('overflow-hidden', open)"
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        class="relative bg-white dark:bg-zinc-800 rounded-xl p-6 w-full max-w-md shadow-xl">

        <div class="flex items-start gap-4 mb-4">

            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M12 3C7.03 3 3 7.03 3 12s4.03 9 9 9
                           9-4.03 9-9-4.03-9-9-9z" />
                </svg>
            </div>

            <div>
                <h2 class="text-lg font-semibold">
                    {{ $title }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1 whitespace-pre-line">
                    {!! $message !!}
                </p>
            </div>

        </div>

        <div class="flex justify-end gap-3 mt-6">

            <button @click="open = false" class="px-4 py-2 border rounded-lg cursor-pointer">
                {{ $cancelText }}
            </button>

            <button wire:click="{{ $action }}" wire:loading.attr="disabled" wire:target="{{ $action }}"
                class="px-4 py-2 bg-red-600 text-white rounded-lg disabled:opacity-50 cursor-pointer">
                <span wire:loading.remove wire:target="{{ $action }}">
                    {{ $confirmText }}
                </span>

                <span wire:loading wire:target="{{ $action }}">
                    Processing...
                </span>
            </button>

        </div>

    </div>
</div>