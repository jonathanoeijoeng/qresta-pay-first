<?php

namespace App\Livewire\Kitchen;

use App\Models\Order;
use App\Events\OrderUpdated; 
use App\Events\OrderSent; 
use Livewire\Component;

new class extends Component
{
    public $order;

    public function getListeners()
    {
        return [
            "echo:order-sent,OrderSent" => '$refresh',
        ];
    }

    public function refreshStatus()
    {
        $this->order->refresh();
    }

    
    public function render()
    {
        $orders = Order::whereIn('status', ['pending', 'processing'])
                            ->with(['table', 'items.menu']) // Eager load items and their menus
                            ->oldest()
                            ->get();

        // $warningMinutes = Setting::where('key', 'kitchen_warning_time')->value('value') ?? 15;
        $warningMinutes = 15;

        return $this->view([
            'orders' => $orders,
            'warningMinutes' => $warningMinutes
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
    <x-header header="Kitchen" description="Cook fast Serve fast" />
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-2xl font-black uppercase tracking-widest">ORDER LIST</h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($orders as $order)
        <div class="bg-brand-50 border border-brand-400 rounded-3xl p-5 shadow-xl flex flex-col">
            <div class="flex justify-between items-start mb-4">
                <span class="text-4xl font-black text-orange-500">#{{ $order->table->number }}</span>
                <div class="text-right">
                    <div
                        class="px-3 py-1 bg-brand-300 rounded-full text-sm uppercase font-bold text-center text-brand-800">
                        {{ $order->status }}
                    </div>
                    <div class="text-sm font-bold mt-2 mr-2" x-data="{ 
                            formatTime() {
                                const start = new Date('{{ $order->created_at->toIso8601String() }}');
                                const now = new Date();
                                const diffInMs = now - start;
                                const diffInMin = Math.floor(diffInMs / 60000);
                                
                                if (diffInMin < 1) return 'Baru saja';
                                return diffInMin + ' menit lalu';
                            },
                            init() {
                                // Update tampilan setiap 30 detik agar menitnya akurat
                                setInterval(() => { this.$el.innerText = this.formatTime() }, 30000);
                            }
                        }" x-init="init()">
                        <span x-text="formatTime()"></span>
                    </div>
                </div>
            </div>

            <div class="mb-6 flex-1">
                <p class="text-zinc-400 text-xs uppercase font-bold tracking-widest mb-2">Items</p>
                <ul class="space-y-1">
                    @forelse($order->items as $item)
                    <li class="text-sm font-medium">
                        {{ $item->quantity }}x {{ $item->menu->name }}
                        @if($item->notes)
                        <span class="text-xs text-zinc-500 italic">({{ $item->notes }})</span>
                        @endif
                    </li>
                    @empty
                    <li class="text-sm text-zinc-400 italic">No items</li>
                    @endforelse
                </ul>
            </div>

            <div class="flex gap-2">
                @if($order->status == 'pending')
                <button wire:click="updateStatus({{ $order->id }}, 'processing')"
                    class="flex-1 bg-orange-600 hover:bg-orange-500 py-5 rounded-2xl tracking-widest font-black text-brand-50 uppercase transition-all">
                    Cook
                </button>
                @else
                <button wire:click="updateStatus({{ $order->id }}, 'completed')"
                    class="flex-1 bg-green-600 hover:bg-green-500 py-5 rounded-2xl tracking-widest font-black text-brand-50 uppercase transition-all">
                    Serve
                </button>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>