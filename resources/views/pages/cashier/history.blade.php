<?php

namespace App\Livewire\Cashier;

use Livewire\Component;
use App\Models\Order;
use Livewire\WithPagination;
use Carbon\Carbon;

new class extends Component
{
    use WithPagination;

    public $view = 'daily'; // Default view
    public $currentRoute;
    public $search = '';
    
    public function mount()
    {
        // Simpan nama route saat halaman pertama kali dibuka
        $this->currentRoute = request()->route()->getName();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function setView($mode)
    {
        $this->view = $mode;
        $this->resetPage(); // Reset pagination setiap pindah filter
    }

    public function render()
    {
        $query = Order::query()->where('payment_status', 'paid');

        // Filter berdasarkan View (Daily, Monthly, Yearly)
        if ($this->view === 'daily') {
            $query->whereDate('paid_at', \Carbon\Carbon::today());
        } elseif ($this->view === 'monthly') {
            $query->whereMonth('paid_at', now()->month)->whereYear('paid_at', now()->year);
        } elseif ($this->view === 'yearly') {
            $query->whereYear('paid_at', now()->year);
        }

    // Filter berdasarkan Search (Order Number atau Table ID)
    if ($this->search !== '') {
        $query->where(function($q) {
            $q->where('order_number', 'ilike', '%' . $this->search . '%')
              ->orWhere('table_id', 'ilike', '%' . $this->search . '%');
        });
    }

        return $this->view([
            'orders' => $query->latest('paid_at')->paginate(15),
            'total_income' => $query->sum('total_amount')
        ]);
    }
};
?>

<div>
   @include('pages.cashier.route')
    <flux:separator />
    <div class="flex flex-col md:flex-row justify-between items-center my-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Rekap Transaksi</h2>
            <p class="text-sm text-gray-500">Total Pendapatan: <span class="font-bold text-green-600">IDR {{
                    number_format($total_income, 0, '.', ',') }}</span></p>
        </div>
        <div class="flex bg-gray-100 p-1 rounded-lg mt-4 md:mt-0">
            <button wire:click="setView('daily')"
                class="px-4 py-2 rounded-md text-sm font-medium transition {{ $view === 'daily' ? 'bg-white shadow text-brand-600' : 'text-gray-500 hover:text-gray-700' }}">
                Hari Ini
            </button>
            <button wire:click="setView('monthly')"
                class="px-4 py-2 rounded-md text-sm font-medium transition {{ $view === 'monthly' ? 'bg-white shadow text-brand-600' : 'text-gray-500 hover:text-gray-700' }}">
                Bulan Ini
            </button>
            <button wire:click="setView('yearly')"
                class="px-4 py-2 rounded-md text-sm font-medium transition {{ $view === 'yearly' ? 'bg-white shadow text-brand-600' : 'text-gray-500 hover:text-gray-700' }}">
                Tahun Ini
            </button>
        </div>
    </div>
    <div class="w-full md:w-1/3 mb-6">
        <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </span>
            <input wire:model.live.debounce.300ms="search" type="text"
                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-brand-600 focus:border-brand-600 sm:text-sm"
                placeholder="Cari No. Order atau Meja...">
        </div>
    </div>

    <div class="overflow-x-auto border rounded-xl">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b bg-brand-100 text-gray-600 uppercase text-xs">
                    <th class="p-3 uppercase text-sm font-bold">Waktu</th>
                    <th class="p-3 uppercase text-sm font-bold">No. Order</th>
                    <th class="p-3 uppercase text-sm font-bold">Meja</th>
                    <th class="p-3 uppercase text-sm font-bold">Tipe</th>
                    <th class="p-3 uppercase text-sm font-bold">Metode</th>
                    <th class="p-3 uppercase text-sm font-bold text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y text-sm">
                @forelse($orders as $order)
                <tr class="hover:bg-gray-50">
                    <td class="p-3">{{ Carbon::parse($order->paid_at)->format('H:i') }} <span
                            class="text-xs text-gray-400">({{
                            Carbon::parse($order->paid_at)->format('d M') }})</span></td>
                    <td class="p-3 font-mono font-bold">{{ $order->order_number }}</td>
                    <td class="p-3">Meja {{ $order->table_id }}</td>
                    <td class="p-3">
                        @php
                        // Tentukan warna berdasarkan type
                        $isOnline = $order->payment_type === 'Online';

                        // Mapping nama yang enak dibaca tamu/kasir
                        $displayType = $isOnline ? 'Xendit (Online)' : 'Kasir (Manual)';

                        // Tentukan class Tailwind
                        $badgeClass = $isOnline
                        ? 'bg-blue-100 text-blue-700 border border-blue-200' // Biru untuk Online
                        : 'bg-orange-100 text-orange-700 border border-orange-200'; // Oranye untuk Kasir
                        @endphp
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full {{ $badgeClass }}">
                            @if($isOnline)
                            {{-- Icon Kilat/Digital --}}
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            @else
                            {{-- Icon User/Tangan --}}
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            @endif
                            {{ $displayType }}
                        </span>
                    </td>
                    <td class="p-3">
                        {{ $order->payment_method }}
                    </td>
                    <td class="p-3 text-right font-mono font-bold text-gray-800">IDR {{
                        number_format($order->total_amount, 0,
                        '.', ',') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-6 text-center text-gray-500 italic">Belum ada transaksi di periode ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
</div>