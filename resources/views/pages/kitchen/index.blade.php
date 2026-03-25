<?php

namespace App\Livewire\Kitchen;

use App\Models\Order;
use App\Events\OrderUpdated; // Pastikan Event ini sudah dibuat
use Livewire\Component;

new class extends Component
{
    public function render()
    {
        return $this->view([
            'orders' => Order::whereIn('status', ['pending', 'processing'])
                            ->with('table')
                            ->latest()
                            ->get()
        ])->title('Kitchen');
    }

    public function updateStatus($orderId, $newStatus)
    {
        $order = Order::findOrFail($orderId);
        $order->update(['status' => $newStatus]);

        // INI KUNCINYA: Memicu Reverb untuk berbisik ke HP Tamu
        broadcast(new OrderUpdated($order))->toOthers();
        
        // Notifikasi lokal untuk layar dapur
        session()->flash('message', "Pesanan Meja {$order->table->number} diperbarui!");
    }
}   
?>

<div>
    <x-header header="Kitchen" description="Manajemen user QResta" />
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-2xl font-black uppercase tracking-widest">ORDER LIST</h1>
        <div class="text-xs text-zinc-500 font-mono">Server Time: {{ now()->format('H:i:s') }}</div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($orders as $order)
        <div class="bg-brand-50 border border-brand-400 rounded-3xl p-5 shadow-xl">
            <div class="flex justify-between items-start mb-4">
                <span class="text-4xl font-black text-orange-500">#{{ $order->table->number }}</span>
                <span class="px-3 py-1 bg-brand-300 rounded-full text-[10px] uppercase font-bold tracking-tighter">
                    {{ $order->status }}
                </span>
            </div>

            <div class="mb-6">
                <p class="text-zinc-400 text-xs uppercase font-bold tracking-widest mb-2">Items</p>
                <ul class="space-y-1">
                    {{-- Contoh looping item pesanan --}}
                    <li class="text-sm font-medium">1x Nasi Goreng Gila</li>
                    <li class="text-sm font-medium">1x Es Teh Manis</li>
                </ul>
            </div>

            <div class="flex gap-2">
                @if($order->status == 'pending')
                <button wire:click="updateStatus({{ $order->id }}, 'processing')"
                    class="flex-1 bg-orange-600 hover:bg-orange-500 py-3 rounded-2xl text-xs font-black uppercase transition-all">
                    Cook
                </button>
                @else
                <button wire:click="updateStatus({{ $order->id }}, 'completed')"
                    class="flex-1 bg-green-600 hover:bg-green-500 py-3 rounded-2xl text-xs font-black uppercase transition-all">
                    Serve
                </button>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>