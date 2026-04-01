  <div class="block md:flex justify-between items-center">
        <div class="text-left mb-6">
            <flux:heading size="xl">Cashier</flux:heading>
            <flux:text class="text-base">Monitor active billing, process payments, and manage table turnover in
                real-time.</flux:text>
        </div>
        <div class="flex bg-gray-100 p-1 rounded-lg my-4 md:my-0 w-fit">
            <a href="{{ route('cashier.index') }}" wire:navigate
                class="px-4 py-2 rounded-md text-sm font-medium transition {{ $currentRoute === 'cashier.index' ? 'bg-white shadow text-brand-600' : 'text-gray-500 hover:text-gray-700' }}">
                Overview
            </a>

            <a href="{{ route('cashier.history') }}" wire:navigate
                class="px-4 py-2 rounded-md text-sm font-medium transition {{ $currentRoute === 'cashier.history' ? 'bg-white shadow text-brand-600' : 'text-gray-500 hover:text-gray-700' }}">
                History
            </a>
        </div>
    </div>