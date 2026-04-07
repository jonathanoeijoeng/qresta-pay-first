<?php

use Livewire\Component;
use App\Models\Order;
use App\Event\OrderSent;
use App\Concerns\HasNotification;

new class extends Component {
    use HasNotification;

    public $search = '';
    public $showConfirmPayment = false;
    public $selectedOrderId;
    public $selectedMethod;
    public $selectedOrderTotal;
    public $message = '';
    public $order;
    public $currentRoute;

    public function mount()
    {
        // Simpan nama route saat halaman pertama kali dibuka
        $this->currentRoute = request()->route()->getName();
    }

    public function getListeners()
    {
        $branchId = auth()->user()->branch_id;
        return [
            "echo-private:order-sent-branch.{$branchId},OrderSent" => 'handleNewOrder',
        ];
    }

    public function handleNewOrder(Order $order)
    {
        $this->notifyAndRefresh('kitchen');
    }

    public function confirmPayment($orderId, $method, $total)
    {
        $this->selectedOrderId = $orderId;
        $this->selectedMethod = $method;

        // Gunakan <b> untuk bold dan \n untuk baris baru
        $this->message = "Apakah Anda yakin pesanan Meja <b>{$this->selectedOrderId}</b> sudah dibayar?\n\n" . 'Total: <b>IDR ' . number_format($total, 0, ',', '.') . "</b>\n" . "Metode: <b>{$method}</b>";

        $this->showConfirmPayment = true;
    }

    public function processPayment()
    {
        // Panggil fungsi markAsPaid yang sudah kita buat tadi
        $this->markAsPaid($this->selectedOrderId, $this->selectedMethod);

        // Tutup modal
        $this->showConfirmPayment = false;
    }

    public function markAsPaid($orderId, $method)
    {
        // 1. Validasi Metode Pembayaran yang Diizinkan
        $allowedMethods = ['QRIS', 'CC', 'Debit', 'Cash'];
        if (!in_array($method, $allowedMethods)) {
            return $this->dispatch('notify', ['type' => 'error', 'message' => 'Metode pembayaran tidak valid!']);
        }

        // 2. Cari Order dengan Eager Load untuk Audit Trail (Opsional)
        $order = \App\Models\Order::findOrFail($orderId);

        // 3. Update Database (Atomic Update)
        $order->update([
            'payment_status' => 'paid',
            'payment_method' => $method,
            'paid_at' => now(),
            'payment_type' => 'Kasir',
        ]);

        // 4. Trigger Kitchen: Karena ini Pay First, setelah dibayar status tetap pending
        // agar muncul di layar dapur. Kita pastikan status item adalah pending.
        $order->items()->update(['status' => 'pending']);
        $order->update(['status' => 'pending']);

        // 5. Broadcast ke Reverb
        // Tamu akan melihat layar "Terima Kasih" atau "Sudah Dibayar" secara real-time
        broadcast(new \App\Events\OrderUpdated($order))->toOthers();

        // 6. Notifikasi Sukses
        $this->dispatch('toast', type: 'success', text: "Order #{$order->order_number} sudah dibayar");
    }

    public function render()
    {
        $activeOrders = Order::with(['table', 'items.menu', 'branch']) // Tambahkan branch agar tahu pesanan milik cabang mana
            ->where('payment_status', 'unpaid')
            ->where('status', '!=', 'draft')

            // LOGIKA AKSES:
            // Jika user punya branch_id (Kasir Cabang), filter berdasarkan cabangnya.
            // Jika branch_id Kosong (Super Admin), lewati filter ini (ambil semua).
            ->when(auth()->user()->branch_id, function ($query) {
                return $query->where('branch_id', auth()->user()->branch_id);
            })

            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('order_number', 'ilike', '%' . $this->search . '%')
                        ->orWhereHas('table', function ($t) {
                            $t->where('number', 'ilike', '%' . $this->search . '%');
                        })
                        // Opsional: Super Admin bisa cari berdasarkan nama cabang
                        ->orWhereHas('branch', function ($b) {
                            $b->where('name', 'ilike', '%' . $this->search . '%');
                        });
                });
            })
            ->orderBy('updated_at', 'desc')
            ->get();

        // Grouping Items per Order
        $activeOrders->each(function ($order) {
            $order->grouped_items = $order->items->groupBy('menu_id')->map(function ($group) {
                return (object) [
                    'name' => $group->first()->menu->name,
                    'quantity' => $group->sum('quantity'),
                    'total_subtotal' => $group->sum('subtotal'),
                ];
            });
        });

        return $this->view([
            'activeOrders' => $activeOrders,
        ])->title('Cashier - Dashboard');
    }
};
?>

<div>
    @include('pages.cashier.route')
    <flux:separator />
    <div class="max-w-md mx-auto my-6">
        <div class=" relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-zinc-400 group-focus-within:text-brand-500 transition-colors" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text"
                placeholder="Scan QR or type Order Number / Table..." autofocus
                class="block w-full pl-11 pr-4 py-4 bg-white border-2 border-zinc-100 rounded-3xl text-sm focus:border-brand-500 active:border-brand-500 focus:ring-0 transition-all shadow-sm focus:outline-none ">

            {{-- Tombol Clear jika sedang mencari --}}
            @if ($search)
                <button wire:click="$set('search', '')"
                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-zinc-400 hover:text-red-500">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            @endif
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach ($activeOrders as $order)
            <div class="bg-white border-2 border-zinc-100 rounded-3xl p-5 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <div class="flex items-center gap-2">
                            <h3 class="text-2xl font-black text-zinc-800 underline decoration-brand-500">Meja
                                {{ $order->table->number }}</h3>
                            <div class="text-xs px-2 py-1 rounded-full w-fit text-center my-1 font-semibold"
                                style="background-color: {{ $order->branch->color }}; color: white;">
                                {{ $order->branch->code }}
                            </div>
                        </div>
                        <flux:badge color="{{ $order->status === 'completed served' ? 'green' : 'orange' }}"
                            size="sm">
                            {{ $order->status }}
                        </flux:badge>
                    </div>
                    <div class="text-lg font-bold text-brand-600 mb-4">#{{ $order->order_number }}</div>

                    <div class="space-y-1 mb-4">
                        @foreach ($order->grouped_items as $item)
                            <div class="flex justify-between text-xs font-medium text-zinc-500">
                                <span>{{ $item->quantity }}x {{ $item->name }}</span>
                                <span>IDR {{ number_format($item->total_subtotal, 0, ',', ',') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="border-t border-dashed border-zinc-200 pt-4 mt-auto">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-xs font-bold text-zinc-400 uppercase">Total Tagihan</span>
                        <span class="text-xl font-black text-brand-600">IDR
                            {{ number_format($order->total_amount, 0, ',', ',') }}</span>
                    </div>

                    <div x-data="{ showPayment: false }">
                        <button x-show="!showPayment" @click="showPayment = true"
                            class="w-full bg-brand-600 text-white py-2 rounded-xl font-bold disabled:opacity-50
                        cursor-pointer disabled:cursor-not-allowed transition-all">
                            Bayar Sekarang
                        </button>

                        <div x-show="showPayment" x-transition class="flex flex-wrap gap-2 mt-4">
                            <p class="w-full text-xs font-bold text-zinc-500 uppercase">Pilih Metode Bayar:</p>
                            {{-- Pemetaan Metode -> Warna --}}
                            @php
                                $paymentMethods = [
                                    'QRIS' => 'brand', // Warna utama (misal: Orange)
                                    'CC' => 'blue', // Warna Indigo
                                    'Debit' => 'green', // Warna Biru Muda
                                    'Cash' => 'zinc', // Warna Hijau
                                ];
                            @endphp

                            @foreach ($paymentMethods as $method => $variant)
                                <x-button
                                    wire:click="confirmPayment({{ $order->id }}, '{{ $method }}', {{ $order->total_amount }})"
                                    variant="{{ $variant }}"
                                    class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all">
                                    {{-- Bisa juga ditambah ikon jika mau --}}
                                    {{ $method }}
                                </x-button>
                            @endforeach

                            <button @click="showPayment = false"
                                class="text-[10px] text-zinc-400 underline w-full mt-2">
                                Batal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <x-confirm wire:model="showConfirmPayment" title="Konfirmasi Pembayaran" :message="$message"
        confirmText="Ya, Sudah Bayar" cancelText="Batal" action="processPayment" />
</div>
