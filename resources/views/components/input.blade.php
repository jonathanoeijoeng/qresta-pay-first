@props([
'label' => '',
'name',
'model',
'type' => 'text',
'addonRight' => null,
'addonLeft' => null,
'currency' => false,
'currencyModel' => 'currency' // Properti livewire untuk mata uang
])

<div>
    @if($label)
    <label class="block text-sm font-medium mb-1">
        {{ $label }}
    </label>
    @endif

    <div class="flex">
        {{-- Addon Left (Modifikasi untuk Currency Select) --}}
        @if($currency)
        <select wire:model.live="{{ $currencyModel }}" class="inline-flex items-center px-3 border border-r-0 rounded-l-lg 
                       bg-gray-100 text-gray-900
                       dark:bg-zinc-800 dark:border-brand-600 dark:text-gray-100
                       focus:ring-0 focus:outline-none cursor-pointer">
            <option value="IDR">IDR</option>
            <option value="USD">USD</option>
            <option value="EUR">EUR</option>
            <option value="AED">AED</option>
            <option value="SGD">SGD</option>
        </select>
        @elseif($addonLeft)
        <span class="inline-flex items-center px-4 border border-r-0 rounded-l-lg 
                             bg-gray-100 text-gray-600 text-sm
                             dark:bg-zinc-700 dark:border-brand-600 dark:text-gray-300">
            {{ $addonLeft }}
        </span>
        @endif

        {{-- INPUT (Tetap dengan Alpine.js kamu) --}}
        @if($currency)

        <input type="text" wire:key="amount-{{ $amount ?? 'empty' }}" x-data="{
        raw: null,

        init() {
            this.raw = $wire.get('{{ $attributes->wire('model')->value() }}')

            this.$watch(() => $wire.get('{{ $attributes->wire('model')->value() }}'), value => {
                this.raw = value
            })
        },

        format(value) {
            if (!value) return ''

            let parts = value.toString().split('.')
            let integer = parts[0]
            let decimal = parts[1] ?? null

            integer = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ',')

            return decimal !== null ? integer + '.' + decimal : integer
        },

        get display() {
            return this.format(this.raw)
        },

        set display(val) {
        let clean = val.replace(/[^0-9.]/g, '')

        const firstDot = clean.indexOf('.')

        if (firstDot !== -1) {
            const before = clean.slice(0, firstDot + 1)
            const after = clean.slice(firstDot + 1).replace(/\./g, '')
            clean = before + after
        }

        this.raw = clean === '' ? null : clean

        $wire.set('{{ $attributes->wire('model')->value() }}', this.raw)
            }
                }" x-model="display"
            class="w-full border shadow shadow-zinc-200 dark:shadow-zinc-900 px-3 py-2 focus:outline-none
                    focus:ring
                    focus:border-green-400 bg-white dark:bg-zinc-700 dark:border-brand-600 dark:text-white rounded-r-lg" />



        @else
        {{-- Normal Mode --}}
        <input type="{{ $type }}" {{ $attributes->merge([
        'class' => 'w-full border shadow shadow-zinc-200 dark:shadow-zinc-900 px-3 py-2
        focus:outline-none focus:ring focus:border-brand-400
        bg-white dark:bg-zinc-700 dark:border-brand-600 dark:text-white ' .
        ($addonLeft && $addonRight ? 'rounded-none' :
        ($addonLeft ? 'rounded-r-lg' :
        ($addonRight ? 'rounded-l-lg' : 'rounded-lg')))
        ]) }}>

        @endif

        {{-- Addon Right --}}
        @if($addonRight)
        <span class="inline-flex items-center px-4 border border-l-0 rounded-r-lg 
                             bg-gray-100 text-gray-600 text-sm
                             dark:bg-zinc-700 dark:border-brand-600 dark:text-gray-300">
            {{ $addonRight }}
        </span>
        @endif
    </div>

    @error($name)
    <span class="text-sm text-red-500 mt-1 block">
        {{ $message }}
    </span>
    @enderror
</div>