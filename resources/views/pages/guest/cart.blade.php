<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\GlobalSetting;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Events\OrderSent;

new class extends Component {
    public $editingItemId = null;
    public $editingNote = '';
    public bool $showEditModal = false;
    public bool $isConfirmed = false; // Flag untuk pindah ke view ringkasan & bayar
    public $order;
    public $orderNumber;

    public function mount()
    {
        // Ambil order_number dari URL (?order=QPF-...)
        $this->orderNumber = request()->query('order');

        if (!$this->orderNumber) {
            // Jika tidak ada di URL, coba fallback ke session (opsional)
            return redirect()->route('guest.menu');
        }

        // Cari order berdasarkan number
        $this->order = \App\Models\Order::with('items.menu')
            ->where('order_number', $this->orderNumber)
            ->where('payment_status', 'unpaid') // Pastikan hanya bisa akses yang belum bayar
            ->first();

        if (!$this->order) {
            return redirect()->route('guest.menu')->with('error', 'Pesanan tidak ditemukan');
        }

        // Pastikan meja di order cocok dengan meja di session (Security Cross-Check)
        if ($this->order->table_id !== session('customer_table_id')) {
            return redirect()->route('invalid-access');
        }
    }

    public function editNote($itemId)
    {
        $item = OrderItem::find($itemId);
        if ($item) {
            $this->editingItemId = $itemId;
            $this->editingNote = $item->notes;
            $this->showEditModal = true;
        }
    }

    public function updateNote()
    {
        $item = OrderItem::find($this->editingItemId);
        if ($item) {
            $duplicate = OrderItem::where('order_id', $item->order_id)
                ->where('menu_id', $item->menu_id)
                ->where('notes', $this->editingNote ?: null)
                ->where('id', '!=', $item->id)
                ->first();

            if ($duplicate) {
                $duplicate->increment('quantity', $item->quantity);
                $duplicate->update(['subtotal' => $duplicate->quantity * $duplicate->price_at_order]);
                $item->delete();
            } else {
                $item->update(['notes' => $this->editingNote ?: null]);
            }
        }
        $this->reset(['showEditModal', 'editingItemId', 'editingNote']);
    }

    #[Computed]
    public function taxPercentage()
    {
        return Cache::remember('settings_tax_percentage', 3600, function () {
            $value = GlobalSetting::where('key', 'tax_percentage')->value('value');
            return $value !== null ? (int) $value : 10;
        });
    }

    #[Computed]
    public function subtotal()
    {
        $orderId = session('active_order_id') ?? session('merging_order_id');
        return OrderItem::where('order_id', $orderId)->sum('subtotal');
    }

    #[Computed]
    public function taxAmount()
    {
        return $this->subtotal * ($this->taxPercentage / 100);
    }

    #[Computed]
    public function grandTotal()
    {
        return $this->subtotal + $this->taxAmount;
    }

    public function updateQty($itemId, $delta)
    {
        $item = OrderItem::find($itemId);
        if (!$item) {
            return;
        }

        $newQty = $item->quantity + $delta;
        if ($newQty <= 0) {
            $item->delete();
        } else {
            $item->update([
                'quantity' => $newQty,
                'subtotal' => $newQty * $item->price_at_order,
            ]);
        }
    }

    public function confirmOrder()
    {
        $order = Order::with('items')->where('order_number', $this->orderNumber)->first();

        if (!$order || $order->items->isEmpty()) {
            return $this->dispatch('notify', ['type' => 'error', 'message' => 'Keranjang kosong!']);
        }

        $order->update([
            'status' => 'pending',
            'total_amount' => $this->grandTotal,
            'tax_amount' => $this->taxAmount,
            'tax_percentage' => $this->taxPercentage,
            'confirmed_at' => now(),
        ]);

        $this->isConfirmed = true;
        broadcast(new OrderSent($order))->toOthers();
    }

    public function pay($method)
    {
        // Logic arahkan ke pembayaran
        if (!$this->order) {
            // Jika hilang (misal session habis), coba ambil lagi dari session
            $this->order = \App\Models\Order::find(session('active_order_id'));
        }

        if (!$this->order) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Data pesanan tidak ditemukan.']);
            return;
        }

        if ($method === 'cashier') {
            return redirect()->route('guest.order-status', $this->order->order_number);
        }

        if ($method === 'xendit') {
            try {
                $apiKey = config('services.xendit.key');
                // Kirim request ke API Xendit secara manual
                // withBasicAuth akan otomatis melakukan Base64 encoding {key}:
                $response = Http::withBasicAuth($apiKey, '')->post('https://api.xendit.co/v2/invoices', [
                    'external_id' => (string) $this->order->order_number,
                    'description' => "Pembayaran QResta #{$this->order->order_number} - Meja {$this->order->table->number}",
                    'amount' => (float) $this->order->total_amount,
                    'currency' => 'IDR',
                    'payment_methods' => [
                        'QRIS', // Wajib ada untuk kemudahan scan
                        'CREDIT_CARD', // Mengaktifkan opsi Kartu Kredit (Visa/Mastercard/JCB)
                        'BCA', // Virtual Account tetap disarankan sebagai cadangan
                        'BNI',
                        'BRI',
                        'MANDIRI',
                    ],
                    'customer' => [
                        'given_names' => $this->order->customer_name ?? 'Tamu Meja ' . $this->order->table->number,
                    ],
                    'success_redirect_url' => route('guest.order-status', $this->order->order_number),
                    'failure_redirect_url' => route('guest.order-status', $this->order->order_number),
                ]);

                if ($response->failed()) {
                    throw new \Exception($response->body());
                }

                $invoice = $response->json();

                // Update database Intel NUC Anda
                $this->order->update([
                    'payment_status' => 'unpaid',
                ]);

                // Redirect ke portal pembayaran Xendit
                return redirect()->away($invoice['invoice_url']);
            } catch (\Exception $e) {
                Log::error('Xendit Manual HTTP Error: ' . $e->getMessage());
                $this->dispatch('toast', type: 'error', text: 'Gagal membuat tagihan. Silakan cek koneksi atau API Key.');
            }
        }
    }

    public function render()
    {
        $orderId = session('active_order_id') ?? session('merging_order_id');
        $items = collect();
        $order = null;

        if ($orderId) {
            $order = Order::find($orderId);
            if ($order) {
                $items = OrderItem::query()->join('menus', 'order_items.menu_id', '=', 'menus.id')->where('order_items.order_id', $order->id)->select('order_items.*', 'menus.name as menu_name')->orderBy('menus.name', 'asc')->with('menu')->get();
            }
        }

        return $this->view([
            'order' => $order,
            'items' => $items,
        ])->layout('components.layouts.guest');
    }
};
?>

<div>
    <div class="min-h-screen bg-zinc-50 dark:bg-zinc-800 pb-40">
        <header
            class="bg-white dark:bg-zinc-700 p-4 border-b border-zinc-100 dark:border-zinc-600 sticky top-0 z-10 flex items-center gap-4">
            <a href="{{ route('guest.menu') }}" class="p-2 bg-brand-600 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                    stroke="currentColor" class="w-5 h-5 text-white">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <h1 class="text-lg text-zinc-800 dark:text-zinc-50 font-bold">
                {{ $isConfirmed ? 'Pembayaran' : 'Review Pesanan' }}
            </h1>
        </header>

        <main class="p-4 space-y-4 pb-32">
            @if (!$isConfirmed)
                {{-- TAMPILAN SEBELUM KONFIRMASI (EDITING MODE) --}}
                @php $pendingItems = $items->whereIn('status', ['pending']); @endphp
                @if ($pendingItems->isNotEmpty())
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-brand-500 mb-3 px-2">Daftar
                            Pesanan</p>
                        @foreach ($pendingItems as $item)
                            <div class="bg-white dark:bg-zinc-600 p-4 rounded-2xl shadow-sm border border-zinc-100 dark:border-zinc-500 mb-3"
                                wire:key="edit-{{ $item->id }}">
                                <div class="flex items-start gap-4">
                                    <div class="flex-1">
                                        <h3 class="font-bold text-zinc-900 dark:text-zinc-50 leading-tight">
                                            {{ $item->menu->name }}</h3>
                                        <button wire:click="editNote({{ $item->id }})"
                                            class="text-[10px] text-zinc-500 font-bold mt-1">
                                            {{ $item->notes ? '"' . $item->notes . '"' : '+ catatan' }}
                                        </button>
                                        <p class="text-brand-600 font-bold text-sm mt-2">IDR
                                            {{ number_format($item->price_at_order, 0, '.', ',') }}</p>
                                    </div>
                                    <div
                                        class="flex items-center gap-3 bg-zinc-100 dark:bg-zinc-500 px-3 py-1.5 rounded-full border border-zinc-200">
                                        <button wire:click="updateQty({{ $item->id }}, -1)"
                                            class="font-black px-1">-</button>
                                        <span class="text-xs font-bold w-4 text-center">{{ $item->quantity }}</span>
                                        <button wire:click="updateQty({{ $item->id }}, 1)"
                                            class="font-black px-1">+</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                {{-- TAMPILAN SETELAH KONFIRMASI (SUMMARY & PAYMENT) --}}
                <div
                    class="bg-white dark:bg-zinc-700 rounded-3xl p-6 shadow-sm border border-zinc-100 dark:border-zinc-600 animate-in fade-in zoom-in duration-300">
                    <h2 class="text-lg font-black mb-4">Ringkasan Pesanan</h2>
                    <div class="space-y-4">
                        @foreach ($items as $item)
                            <div class="flex justify-between items-start text-sm">
                                <div>
                                    <p class="font-bold text-zinc-800 dark:text-zinc-100">{{ $item->menu->name }}</p>
                                    <p class="text-xs text-zinc-400">{{ $item->quantity }}x @ IDR
                                        {{ number_format($item->price_at_order, 0, '.', ',') }}</p>
                                </div>
                                <span class="font-bold">IDR {{ number_format($item->subtotal, 0, '.', ',') }}</span>
                            </div>
                        @endforeach

                        <div class="pt-4 border-t border-zinc-100 space-y-2">
                            <div class="flex justify-between text-xs">
                                <span class="text-zinc-500">Subtotal</span>
                                <span>IDR {{ number_format($this->subtotal, 0, '.', ',') }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-zinc-500">Pajak ({{ $this->taxPercentage }}%)</span>
                                <span>IDR {{ number_format($this->taxAmount, 0, '.', ',') }}</span>
                            </div>
                            <div class="flex justify-between text-xl font-black text-brand-600 pt-2">
                                <span>Total</span>
                                <span>IDR {{ number_format($this->grandTotal, 0, '.', ',') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </main>

        {{-- STICKY BOTTOM ACTIONS --}}
        <div
            class="fixed bottom-0 left-0 right-0 bg-white dark:bg-zinc-700 p-6 border-t border-zinc-100 dark:border-zinc-600 shadow-[0_-10px_40px_rgba(0,0,0,0.05)] z-50">
            @if (!$isConfirmed)
                <div class="space-y-4">
                    <a href="{{ route('guest.menu', ['order' => $order->order_number]) }}"
                        class="flex items-center justify-center gap-2 w-full py-3 border-2 border-dashed border-zinc-200 dark:border-zinc-500 rounded-2xl text-zinc-500 font-bold text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Add Another Item
                    </a>

                    <div class="flex justify-between items-center px-2">
                        <span class="text-sm font-bold">Estimasi Total</span>
                        <span class="text-xl font-black text-brand-600">IDR
                            {{ number_format($this->grandTotal, 0, '.', ',') }}</span>
                    </div>

                    <button wire:click="confirmOrder" wire:loading.attr="disabled"
                        class="w-full bg-brand-600 text-white py-4 rounded-2xl font-bold shadow-lg active:scale-95 transition-all">
                        <span wire:loading.remove>Konfirmasi Pesanan</span>
                        <span wire:loading>Memproses...</span>
                    </button>
                </div>
            @else
                <div class="grid grid-cols-2 gap-3 animate-in slide-in-from-bottom duration-500">
                    <button wire:click="pay('xendit')"
                        class="bg-brand-600 text-white py-4 rounded-2xl font-bold text-sm shadow-lg active:scale-95 hover:bg-brand-700 cursor-pointer">
                        Bayar Disini
                    </button>
                    <button wire:click="pay('cashier')"
                        class="bg-zinc-900 hover:bg-zinc-700 text-white py-4 rounded-2xl font-bold text-sm shadow-lg active:scale-95 cursor-pointer">
                        Bayar di Kasir
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL CATATAN --}}
    @if ($showEditModal)
        <div class="fixed inset-0 bg-zinc-900/60 z-[100] flex items-end justify-center">
            <div class="bg-white w-full max-w-md rounded-t-[2.5rem] p-8 animate-in slide-in-from-bottom duration-300">
                <div class="w-12 h-1 bg-zinc-200 rounded-full mx-auto mb-6"></div>
                <h3 class="text-lg font-black mb-2">Catatan Pesanan</h3>
                <textarea wire:model="editingNote"
                    class="w-full bg-zinc-50 border-none rounded-3xl p-5 text-sm focus:ring-2 focus:ring-brand-500 h-32 resize-none"
                    placeholder="Contoh: Sangat pedas..."></textarea>
                <div class="mt-8 flex gap-4">
                    <button wire:click="$set('showEditModal', false)"
                        class="flex-1 py-4 text-zinc-400 font-bold">Batal</button>
                    <button wire:click="updateNote"
                        class="flex-[2] bg-zinc-900 text-white py-4 rounded-2xl font-bold">Simpan</button>
                </div>
            </div>
        </div>
    @endif
</div>
