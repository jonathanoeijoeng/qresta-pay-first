<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Order;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer; 
use Livewire\Attributes\On; 
use App\Events\OrderUpdated; 

new class extends Component
{
    public $order;
    public $qrCodeSvg;
    public $showQrModal = false;
    public $showPaymentModal = false;
    public $showConfirmMerge = false;

    public function mount($order_number)
    {
        $this->order = Order::with(['items.menu','table'])
            ->where('order_number', $order_number)
            ->firstOrFail();
        $this->generateQrCode();

    }

    public function checkAndMergeOrder()
    {
        if ($this->order->payment_status === 'unpaid') {
            // Simpan ID order yang ditemukan ke property agar bisa diakses oleh mergeOrder()
            $this->showConfirmMerge = true;
        } else {
            return redirect()->route('guest.menu');
        }
    }

    public function goToMenu()
    {
        // Kita simpan di session agar halaman menu tahu ada "Order Gantung"
        session(['merging_order_id' => $this->order->id]);
        
        return redirect()->route('guest.menu');
    }

    public function getListeners()
    {
        return [
            "echo:order.{$this->order->order_number},OrderUpdated" => 'refreshStatus',
        ];
    }

    public function refreshStatus()
    {
        $this->order->refresh();
        if ($this->order->payment_status === 'paid') {
        // Memicu Alpine.js untuk menampilkan ucapan terima kasih
        $this->dispatch('order-paid-success');
    }
    }

    public function generateQrCode()
    {
        // 1. Set style (Ukuran 150px)
        $renderer = new ImageRenderer(
            new RendererStyle(150),
            new SvgImageBackEnd()
        );

        // 2. Buat Writer
        $writer = new Writer($renderer);

        // 3. Generate SVG berdasarkan nomor pesanan
        $this->qrCodeSvg = $writer->writeString($this->order->order_number);
    }  


    #[Layout('components.layouts.guest')]
    public function render()
    {
        $summaryItems = collect();

        if ($this->order) {
            $summaryItems = $this->order->items->groupBy('menu_id')->map(function ($group) {
                return [
                    'name'           => $group->first()->menu->name,
                    'quantity'       => $group->sum('quantity'),
                    'total_subtotal' => $group->sum('subtotal'),
                ];
            });
        }
        return $this->view([
            'summaryItems' => $summaryItems
        ])->layout('components.layouts.guest');
    }
};
?>

<div class="max-w-md mx-auto min-h-screen bg-zinc-50 p-6 dark:bg-zinc-800">
    <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-brand-100 rounded-full mb-4">
            @if($order->status == 'pending')
            <div class="animate-bounce text-brand-600 text-3xl">⏳</div>
            @elseif($order->status == 'processing')
            <div class="animate-spin text-brand-600 text-3xl">🍳</div>
            @else
            <div class="text-green-600 text-3xl">✅</div>
            @endif
        </div>
        <h1 class="text-2xl font-black text-brand-600 dark:text-brand-600 uppercase">Pesanan Diterima!</h1>
        <p class="text-zinc-500 dark:text-zinc-100">Nomor Antrian: <span
                class="font-bold text-brand-600 dark:text-brand-600">#{{
                $order->order_number }}</span>
        </p>
        @if($order->status == 'completed')
        <div class="mt-8">
            <button wire:click="$set('showPaymentModal', true)"
                class="w-full bg-brand-500 hover:bg-brand-600 py-4 rounded-2xl text-white font-black uppercase tracking-widest shadow-lg transition-all">
                Continue to Payment
            </button>
        </div>
        @endif
        <div x-show="$wire.showPaymentModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl p-6 w-full max-w-md shadow-2xl">
                <h3 class="text-2xl font-black text-zinc-800 mb-2">Pilih Metode Bayar</h3>
                <p class="text-zinc-500 text-sm mb-6">Bagaimana Anda ingin menyelesaikan pembayaran?</p>

                <div class="grid grid-cols-1 gap-4">
                    <button wire:click="processPayment('midtrans')"
                        class="flex items-center gap-4 p-4 border-2 border-zinc-100 rounded-2xl hover:border-brand-500 transition-all text-left">
                        <div class="bg-blue-100 p-3 rounded-xl text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <span class="block font-bold text-zinc-800">Bayar di Sini</span>
                            <span class="text-xs text-zinc-400 text-slate-500">QRIS, Virtual Account, Kartu
                                Kredit</span>
                        </div>
                    </button>

                    <button wire:click="$set('showQrModal', true)"
                        class="flex items-center gap-4 p-4 border-2 border-zinc-100 rounded-2xl hover:border-brand-500 transition-all text-left">
                        <div class="bg-green-100 p-3 rounded-xl text-green-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <span class="block font-bold text-zinc-800">Bayar di Kasir</span>
                            <span class="text-xs text-zinc-400">Tunai atau Debit melalui petugas</span>
                        </div>
                    </button>
                </div>

                <button @click="$wire.showPaymentModal = false"
                    class="w-full mt-6 text-zinc-400 font-bold text-sm uppercase">Batal</button>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-400 rounded-3xl p-6 shadow-sm border border-zinc-100 mb-6">
        <div class="space-y-6">
            @php
            $steps = [
            ['id' => 'pending', 'label' => 'Pesanan Terkirim', 'desc' => 'Menunggu konfirmasi dapur'],
            ['id' => 'processing', 'label' => 'Sedang Dimasak', 'desc' => 'Koki sedang menyiapkan menu'],
            ['id' => 'completed', 'label' => 'Siap Disajikan', 'desc' => 'Pesanan segera diantarkan ke meja ' .
            $order->table->number],
            ];
            $currentReached = true;
            @endphp

            @foreach($steps as $step)
            <div class="flex gap-4">
                <div class="flex flex-col items-center">
                    <div
                        class="w-6 h-6 rounded-full {{ $order->status == $step['id'] ? 'bg-brand-600 ring-4 ring-brand-100' : ($currentReached ? 'bg-brand-600' : 'bg-zinc-200') }}">
                    </div>
                    @if(!$loop->last) <div
                        class="w-0.5 h-10 {{ $currentReached && $order->status != $step['id'] ? 'bg-brand-600' : 'bg-zinc-200' }}">
                    </div> @endif
                </div>
                <div>
                    <h3
                        class="font-bold text-sm {{ $order->status == $step['id'] ? 'text-brand-600' : 'text-zinc-800' }}">
                        {{ $step['label'] }}</h3>
                    <p class="text-xs text-zinc-500">{{ $step['desc'] }}</p>
                </div>
            </div>
            @if($order->status == $step['id']) @php $currentReached = false; @endphp @endif
            @endforeach
        </div>
    </div>

    <div class="bg-zinc-600 text-zinc-50 rounded-3xl p-6 shadow-xl">
        <h4 class="text-xs uppercase tracking-widest font-bold text-zinc-100 mb-4">Ringkasan Pesanan</h4>
        <div class="space-y-3 mb-4 border-b border-white/10 pb-4">
            @foreach($summaryItems as $item)
            <div class="flex justify-between text-sm">
                <span>{{ $item['quantity'] }}x {{ $item['name'] }}</span>
                <span>IDR {{ number_format($item['total_subtotal'], 0, ',', ',') }}</span>
            </div>
            @endforeach
        </div>
        <div class="flex justify-between">
            <span>Sub Total</span>
            <span class="text-brand-600 font-semibold">IDR {{ number_format($order->items->sum('subtotal'), 0, ',', ',')
                }}</span>
        </div>
        <div class="flex justify-between">
            <div class="flex gap-2 items-baseline"><span>PB 1</span><span>{{ number_format($order->tax_percentage, 0,
                    ',', ',')
                    }}%</span></div>
            <span class="text-brand-600 font-semibold">IDR {{ number_format($order->tax_amount, 0, ',', ',') }}</span>
        </div>
        <div class="flex justify-between font-bold text-lg">
            <span>Total Bayar</span>
            <span class="text-brand-600">IDR {{ number_format($order->total_amount, 0, ',', ',') }}</span>
        </div>
    </div>

    <div class="mt-8 text-center">
        <x-button wire:click="checkAndMergeOrder" class="text-brand-600 font-bold text-sm">Pesan
            Lagi?</x-button>
    </div>
    {{-- Modal to show image --}}
    @if($showQrModal)
    <div
        class="fixed inset-0 bg-black/90 z-[10001] flex items-center justify-center p-6 animate-in fade-in duration-300">
        {{-- Tombol Tutup di Pojok Kanan Atas --}}
        <button wire:click="$set('showQrModal', false)"
            class="absolute top-6 right-6 text-white/50 hover:text-white transition-colors">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- Kontainer Utama QR --}}
        <div
            class="w-full max-w-sm aspect-square bg-white p-4 rounded-[2.5rem] shadow-[0_0_50px_rgba(255,255,255,0.1)] flex flex-col items-center justify-center transform animate-in zoom-in-95 duration-300">
            <h3 class="text-zinc-900 font-black text-xl tracking-tight">QResta</h3><span>{{
                $order->table->branch->name
                }}</span>
            <div class="w-full h-full [&>svg]:w-full [&>svg]:h-full">
                {!! $qrCodeSvg !!}
            </div>

            <div class="mt-6 text-center">
                <p class="text-zinc-500 text-xs font-medium uppercase tracking-widest">Tunjukkan QR code ini ke
                    kasir
                </p>
                <p class="text-zinc-400 text-xs font-medium uppercase tracking-widest mt-1">Meja {{
                    $order->table->number }}
                </p>
            </div>
        </div>

        {{-- Overlay Klik untuk Tutup --}}
        <div wire:click="$set('showQrModal', false)" class="absolute inset-0 -z-10"></div>
    </div>
    @endif

    {{-- Modal konfirmasi order baru --}}
    <div x-data="{ open: @entangle('showConfirmMerge') }" x-show="open"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-zinc-900/80 backdrop-blur-sm" x-cloak>

        <div class="bg-white rounded-[2.5rem] p-8 w-full max-w-sm shadow-2xl border border-zinc-100" x-show="open"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-8"
            x-transition:enter-end="opacity-100 translate-y-0">

            <div class="flex justify-center mb-6">
                <div class="bg-brand-100 p-4 rounded-full">
                    {{-- Icon Plus/Merge --}}
                    <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4">
                        </path>
                    </svg>
                </div>
            </div>

            <h3 class="text-2xl font-black text-zinc-800 text-center mb-3">Tambah Pesanan?</h3>
            <p class="text-zinc-500 text-center leading-relaxed">
                Kami menemukan pesanan Anda sebelumnya yang <span class="font-bold">belum dibayar</span>.
            </p>
            <p class="text-zinc-500 text-center leading-relaxed mb-8">
                Item baru akan ditambahkan ke tagihan tersebut.
            </p>


            <div class="space-y-3">
                <button wire:click="goToMenu"
                    class="w-full bg-brand-600 hover:bg-brand-700 py-5 rounded-2xl text-white font-black uppercase tracking-widest shadow-lg shadow-brand-200 transition-all active:scale-95">
                    Konfirmasi & Tambah
                </button>

                <button @click="open = false"
                    class="w-full py-3 text-zinc-400 font-bold text-sm uppercase tracking-wider hover:text-zinc-600">
                    Kembali
                </button>
            </div>
        </div>
    </div>
    <div <div x-data="{ 
        showThanks: false,
     }" x-on:order-paid-success.window="showThanks = true" class="relative">

        <template x-if="showThanks">
            <div class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-zinc-900/90 backdrop-blur-md">
                <div
                    class="bg-white dark:bg-zinc-800 rounded-[3rem] p-10 text-center shadow-2xl border-4 border-brand-500">

                    <div
                        class="w-24 h-24 bg-brand-100 text-brand-600 rounded-full flex items-center justify-center mx-auto mb-6 animate-bounce">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                    </div>

                    <h2 class="text-3xl font-black text-zinc-800 dark:text-zinc-100 mb-2">Lunas!</h2>
                    <p class="text-zinc-500 dark:text-zinc-400 mb-8 leading-relaxed">
                        Terima kasih, {{ $order->customer_name ?? 'Pelanggan setia' }} yang terhormat!<br>
                        Pembayaran Anda telah kami terima.
                    </p>

                    <p class="mt-10 text-[10px] text-zinc-400 italic">Sesi Anda telah berakhir. Silakan tutup halaman
                        ini.</p>
                </div>
            </div>
        </template>

    </div>
</div>