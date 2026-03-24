<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use App\Models\Order;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $editingItemId = null;
    public $editingNote = '';
    public bool $showEditModal = false; 
    
    public function editNote($itemId)
    {
        $item = \App\Models\OrderItem::find($itemId);
        if ($item) {
            $this->editingItemId = $itemId;
            $this->editingNote = $item->notes;
            $this->showEditModal = true;
        }
    }

    public function updateNote()
    {
        $item = \App\Models\OrderItem::find($this->editingItemId);
        
        if ($item) {
            // Cek apakah ada item lain dengan Menu ID & Note yang sama persis setelah diedit
            $duplicate = \App\Models\OrderItem::where('order_id', $item->order_id)
                ->where('menu_id', $item->menu_id)
                ->where('notes', $this->editingNote ?: null)
                ->where('id', '!=', $item->id)
                ->first();

            if ($duplicate) {
                // Jika jadi duplikat, gabungkan quantity-nya ke yang sudah ada
                $duplicate->increment('quantity', $item->quantity);
                $duplicate->update(['subtotal' => $duplicate->quantity * $duplicate->price_at_order]);
                $item->delete();
            } else {
                // Jika unik, cukup update catatannya
                $item->update(['notes' => $this->editingNote ?: null]);
            }
        }

        $this->reset(['showEditModal', 'editingItemId', 'editingNote']);
    }

    #[Computed]
    public function taxAmount(): int
    {
        // PB1 sebesar 10% dari total_amount (subtotal)
        return (int) (($this->order->total_amount ?? 0) * 0.10);
    }

    #[Computed]
    public function grandTotal(): int
    {
        // Total Pesanan + Pajak
        return (int) (($this->order->total_amount ?? 0) + $this->taxAmount);
    }

    #[Computed]
    public function order()
    {
        $order = Order::where('table_id', session('customer_table_id'))
            ->where('status', 'pending')
            ->first();

        if ($order) {
            // Kita timpa relasi items dengan query yang sudah di-sort paten
            $order->setRelation('items', $order->items()
                ->join('menus', 'order_items.menu_id', '=', 'menus.id')
                ->orderBy('menus.name', 'asc')
                ->select('order_items.*') // Penting: hanya ambil kolom order_items agar ID tidak tertukar
                ->get()
            );
        }

        return $order;
    }

    public function getOrderProperty()
    {
        return Order::with('items.menu')
            ->where('table_id', session('customer_table_id'))
            ->where('status', 'pending')
            ->first();
    }

    public function updateQty($itemId, $delta)
    {
        $item = \App\Models\OrderItem::find($itemId);
        if (!$item) return;

        $newQty = $item->quantity + $delta;

        if ($newQty <= 0) {
            $item->delete();
        } else {
            $item->update([
                'quantity' => $newQty,
                'subtotal' => $newQty * $item->price_at_order
            ]);
        }

        // Update total di tabel orders
        $order = $this->order;
        $order->update([
            'total_amount' => $order->items()->sum('subtotal')
        ]);
        
        // Jika item habis, hapus order atau biarkan kosong
        if ($order->items()->count() === 0) {
            // Optional: $order->delete();
        }
    }

    public function render()
    {
        // 1. Ambil Order Utama
        $order = \App\Models\Order::where('table_id', session('customer_table_id'))
            ->where('status', 'pending')
            ->first();

        $items = collect(); // Default kosong jika tidak ada order

        if ($order) {
            // 2. Ambil Items dengan JOIN manual agar SORT alfabetis terkunci di DB
            $items = \App\Models\OrderItem::query()
                ->join('menus', 'order_items.menu_id', '=', 'menus.id')
                ->where('order_items.order_id', $order->id)
                ->select('order_items.*', 'menus.name as menu_name') // Ambil alias name untuk kepastian
                ->orderBy('menus.name', 'asc') // A-Z
                ->with('menu') // Tetap load relasi menu untuk gambar/info lainnya
                ->get();
        }

        return $this->view([
            'order' => $order,
            'items' => $items, // Kita kirim variabel $items terpisah
        ])->layout('components.layouts.guest');
    }
}
?>

<div>
    <div class="min-h-screen bg-zinc-50 pb-40">
        <header class="bg-white p-4 border-b border-zinc-100 sticky top-0 z-10 flex items-center gap-4">
            <a href="{{ route('guest.menu') }}" class="p-2 bg-zinc-50 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                    stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <h1 class="text-lg font-bold">Review Pesanan</h1>
        </header>

        <main class="p-4 space-y-3 pb-32">
            @if($items->count() > 0)
            @foreach($items as $item)
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-zinc-100 mb-3"
                wire:key="item-row-{{ $item->id }}">
                <div class="flex items-start gap-4">
                    <div class="flex-1">
                        <h3 class="font-bold text-zinc-900 leading-tight">{{ $item->menu->name }}</h3>

                        @if($item->notes)
                        <button wire:click="editNote({{ $item->id }})" class="flex items-start gap-1.5 mt-1 group">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                class="w-3.5 h-3.5 text-zinc-400 mt-0.5 group-active:text-orange-500">
                                <path
                                    d="M5.433 13.917l1.262-3.155A4 4 0 017.58 9.42l6.92-6.918a2.121 2.121 0 013 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 01-.65-.65z" />
                            </svg>
                            <span class="text-xs text-zinc-500 italic border-b border-dotted border-zinc-300">
                                "{{ $item->notes }}"
                            </span>
                        </button>
                        @else
                        <button wire:click="editNote({{ $item->id }})" class="text-[10px] text-zinc-400 font-bold mt-2">
                            + tambah catatan
                        </button>
                        @endif

                        <p class="text-orange-500 font-bold text-sm mt-2">
                            IDR {{ number_format($item->price_at_order, 0, '.', ',') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-3 bg-zinc-100 px-3 py-1.5 rounded-full shadow-inner">
                        <button wire:click="updateQty({{ $item->id }}, -1)" class="text-zinc-500 font-black">-</button>
                        <span class="text-xs font-bold w-4 text-center">{{ $item->quantity }}</span>
                        <button wire:click="updateQty({{ $item->id }}, 1)" class="text-zinc-500 font-black">+</button>
                    </div>
                </div>
            </div>
            @endforeach
            @else
            <div class="py-20 text-center">
                <p class="text-zinc-400">Belum ada menu yang dipilih.</p>
                <a href="{{ route('guest.menu') }}" class="text-orange-500 font-bold mt-2 inline-block">Kembali ke
                    Menu</a>
            </div>
            @endif
        </main>

        @if($this->order && $this->order->items->count() > 0)
        <div
            class="fixed bottom-0 left-0 right-0 bg-white p-6 border-t border-zinc-100 shadow-[0_-10px_40px_rgba(0,0,0,0.05)] z-20">

            <div class="space-y-2 mb-2 border-b border-zinc-200 pb-4">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-zinc-500">Subtotal</span>
                    <span class="text-zinc-900 font-medium">IDR {{ number_format($this->order->total_amount, 0, '.',
                        ',') }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-zinc-500">PB1 (10%)</span>
                    <span class="text-zinc-900 font-medium">IDR {{ number_format($this->taxAmount, 0, '.', ',')
                        }}</span>
                </div>
            </div>

            <div class="flex justify-between items-center mb-6">
                <span class="text-zinc-900 font-bold">Total Pembayaran</span>
                <span class="text-2xl font-black text-orange-600">
                    IDR {{ number_format($this->grandTotal, 0, '.', ',') }}
                </span>
            </div>

            <button wire:click="confirmOrder"
                class="w-full bg-orange-500 text-white py-4 rounded-2xl font-bold text-lg shadow-lg shadow-orange-500/30 active:scale-[0.98] transition-all flex justify-center items-center gap-2">
                <span>Konfirmasi Pesanan</span>
            </button>
        </div>
        @endif
    </div>
    @if($showEditModal)
    <div class="fixed inset-0 bg-zinc-900/60 z-[100] flex items-end justify-center p-0">
        <div class="bg-white w-full max-w-md rounded-t-[2.5rem] p-8 animate-in slide-in-from-bottom duration-300">
            <div class="w-12 h-1 bg-zinc-200 rounded-full mx-auto mb-6"></div>

            <h3 class="text-lg font-black text-zinc-900 mb-2">Ubah Catatan</h3>
            <p class="text-sm text-zinc-500 mb-6 font-medium">Instruksi khusus untuk koki dapur</p>

            <textarea wire:model="editingNote"
                class="w-full bg-zinc-50 border-none rounded-3xl p-5 text-sm focus:ring-2 focus:ring-orange-500 h-32 resize-none placeholder:text-zinc-300"
                placeholder="Contoh: Sangat pedas, pisah kuah..."></textarea>

            <div class="mt-8 flex gap-4">
                <button wire:click="$set('showEditModal', false)"
                    class="flex-1 py-4 text-zinc-400 font-bold">Batal</button>
                <button wire:click="updateNote"
                    class="flex-[2] bg-zinc-900 text-white py-4 rounded-2xl font-bold shadow-xl active:scale-95 transition-all">
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
    @endif
</div>