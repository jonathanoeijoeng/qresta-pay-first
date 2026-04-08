<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Order;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

new class extends Component {
    public $order;
    public $qrCodeSvg;
    public $showQrModal = false;
    public $showPaymentModal = false;
    public $showConfirmMerge = false;

    public function mount($order_number)
    {
        $this->loadOrder($order_number);
        $this->checkIfPaid();
        $this->generateQrCode();
    }

    public function loadOrder($order_number)
    {
        $this->order = Order::with(['items.menu', 'table.branch'])
            ->where('order_number', $order_number)
            ->firstOrFail();
    }

    public function checkIfPaid()
    {
        if ($this->order->payment_status === 'paid') {
            session()->forget(['active_order_id', 'customer_table_id', 'merging_order_id']);
            $this->dispatch('order-paid-success');
        }
    }

    public function checkAndMergeOrder()
    {
        // Langsung arahkan ke menu dengan menyetel session merging
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
        $this->checkIfPaid();
    }

    public function generateQrCode()
    {
        $renderer = new ImageRenderer(new RendererStyle(150), new SvgImageBackEnd());
        $writer = new Writer($renderer);
        $this->qrCodeSvg = $writer->writeString($this->order->order_number);
    }

    public function processPayment()
    {
        try {
            $apiKey = config('services.xendit.key');
            $response = Http::withBasicAuth($apiKey, '')->post('https://api.xendit.co/v2/invoices', [
                'external_id' => (string) $this->order->order_number,
                'description' => "Pembayaran QResta #{$this->order->order_number}",
                'amount' => (float) $this->order->total_amount,
                'currency' => 'IDR',
                'customer' => [
                    'given_names' => $this->order->customer_name ?? 'Meja ' . $this->order->table->number,
                ],
                'success_redirect_url' => route('guest.order-status', $this->order->order_number),
                'failure_redirect_url' => route('guest.order-status', $this->order->order_number),
            ]);

            if ($response->failed()) {
                throw new \Exception($response->body());
            }

            $invoice = $response->json();
            $this->order->update(['payment_token' => $invoice['id']]);

            return redirect()->away($invoice['invoice_url']);
        } catch (\Exception $e) {
            Log::error('Xendit Error: ' . $e->getMessage());
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Gagal membuat tagihan.']);
        }
    }

    #[Layout('components.layouts.guest')]
    public function render()
    {
        // Kelompokkan item untuk tampilan ringkasan yang rapi
        $summaryItems = $this->order->items->groupBy('menu_id')->map(function ($group) {
            return [
                'name' => $group->first()->menu->name,
                'quantity' => $group->sum('quantity'),
                'price' => $group->first()->price_at_order,
                'total' => $group->sum('subtotal'),
            ];
        });

        return $this->view(['summaryItems' => $summaryItems]);
    }
};
?>

<div class="max-w-md mx-auto min-h-screen bg-zinc-50 dark:bg-zinc-900 pb-32">
    <header class="bg-white dark:bg-zinc-800 p-6 shadow-sm sticky top-0 z-40">
        <div class="flex justify-between items-center mb-6">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-zinc-400">Order Status</p>
                <h1 class="text-xl font-black text-zinc-800 dark:text-white">#{{ $order->order_number }}</h1>
            </div>
            <div class="text-right">
                <span class="px-3 py-1 bg-brand-100 text-brand-600 rounded-full text-[10px] font-bold uppercase">
                    Meja {{ $order->table->number }}
                </span>
            </div>
        </div>

        <div class="relative flex justify-between items-start">
            @php
                $steps = [
                    ['id' => 'pending', 'icon' => '⏳', 'label' => 'Confirmed'],
                    ['id' => 'processing', 'icon' => '🍳', 'label' => 'Processed'],
                    ['id' => 'completed-served', 'icon' => '🍽️', 'label' => 'Done'],
                ];
                $statuses = ['pending', 'processing', 'completed-served'];
                $currentIndex = array_search($order->status, $statuses);
            @endphp

            <div class="absolute top-4 left-0 w-full h-0.5 bg-zinc-100 dark:bg-zinc-700 -z-10">
                <div class="h-full bg-brand-500 transition-all duration-500" style="width: {{ $currentIndex * 50 }}%">
                </div>
            </div>

            @foreach ($steps as $index => $step)
                <div class="flex flex-col items-center gap-2">
                    <div
                        class="w-8 h-8 rounded-full flex items-center justify-center text-sm transition-all duration-300
                        {{ $currentIndex >= $index ? 'bg-brand-600 text-white shadow-lg shadow-brand-200' : 'bg-zinc-100 text-zinc-400' }}">
                        @if ($currentIndex > $index)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        @else
                            {{ $step['icon'] }}
                        @endif
                    </div>
                    <span
                        class="text-[10px] font-bold uppercase {{ $currentIndex == $index ? 'text-brand-600' : 'text-zinc-400' }}">
                        {{ $step['label'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </header>

    <main class="p-6 space-y-6">
        @if ($order->payment_status == 'unpaid')
            <div class="bg-brand-600 rounded-[2rem] p-6 text-white shadow-xl shadow-brand-100">
                <div class="flex justify-between items-center mb-4">
                    <p class="text-xs font-bold uppercase opacity-80">Total Belum Dibayar</p>
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
                        </path>
                    </svg>
                </div>
                <h2 class="text-3xl font-black mb-6">IDR {{ number_format($order->total_amount, 0, '.', ',') }}</h2>
                <button wire:click="$set('showPaymentModal', true)"
                    class="w-full bg-white text-brand-600 py-4 rounded-2xl font-black uppercase tracking-widest active:scale-95 transition-all">
                    Bayar Sekarang
                </button>
            </div>
        @endif

        <div class="bg-white dark:bg-zinc-800 rounded-[2rem] p-6 shadow-sm border border-zinc-100 dark:border-zinc-700">
            <h3 class="text-sm font-black text-zinc-800 dark:text-zinc-100 uppercase tracking-widest mb-4">Daftar Menu
            </h3>
            <div class="divide-y divide-zinc-50 dark:divide-zinc-700">
                @foreach ($summaryItems as $item)
                    <div class="py-4 flex justify-between items-center">
                        <div class="flex gap-3 items-center">
                            <span
                                class="w-8 h-8 flex items-center justify-center bg-zinc-50 dark:bg-zinc-700 rounded-lg text-xs font-bold text-brand-600">
                                {{ $item['quantity'] }}x
                            </span>
                            <div>
                                <p class="text-sm font-bold text-zinc-800 dark:text-zinc-200">{{ $item['name'] }}</p>
                                <p class="text-[10px] text-zinc-400">@ IDR
                                    {{ number_format($item['price'], 0, '.', ',') }}</p>
                            </div>
                        </div>
                        <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300">IDR
                            {{ number_format($item['total'], 0, '.', ',') }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 pt-6 border-t-2 border-dashed border-zinc-100 dark:border-zinc-700 space-y-2">
                <div class="flex justify-between text-xs text-zinc-500">
                    <span>Subtotal</span>
                    <span>IDR {{ number_format($order->items->sum('subtotal'), 0, '.', ',') }}</span>
                </div>
                <div class="flex justify-between text-xs text-zinc-500">
                    <span>Pajak ({{ number_format($order->tax_percentage, 0) }}%)</span>
                    <span>IDR {{ number_format($order->tax_amount, 0, '.', ',') }}</span>
                </div>
                <div class="flex justify-between text-lg font-black text-zinc-800 dark:text-white pt-2">
                    <span>Total Akhir</span>
                    <span>IDR {{ number_format($order->total_amount, 0, '.', ',') }}</span>
                </div>
            </div>
        </div>

        <div class="text-center">
            <button wire:click="checkAndMergeOrder"
                class="inline-flex items-center gap-2 text-brand-600 font-bold hover:scale-105 transition-all">
                <div class="p-2 bg-brand-50 rounded-full">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                Mau order menu lagi?
            </button>
        </div>
    </main>

    <div x-show="$wire.showPaymentModal" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/60 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-[2.5rem] p-8 w-full max-w-sm shadow-2xl">
            <h3 class="text-xl font-black text-zinc-800 dark:text-white mb-6">Metode Bayar</h3>
            <div class="space-y-3">
                <button wire:click="processPayment"
                    class="w-full flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-700 rounded-2xl border-2 border-transparent hover:border-brand-500 transition-all">
                    <div class="bg-blue-100 p-2 rounded-lg text-blue-600">💳</div>
                    <div class="text-left">
                        <span class="block font-bold text-sm">Bayar Online</span>
                        <span class="text-[10px] text-zinc-400 uppercase">QRIS, VA, Kartu</span>
                    </div>
                </button>
                <button wire:click="$set('showQrModal', true)"
                    class="w-full flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-700 rounded-2xl border-2 border-transparent hover:border-brand-500 transition-all text-left">
                    <div class="bg-green-100 p-2 rounded-lg text-green-600">🏪</div>
                    <div>
                        <span class="block font-bold text-sm">Bayar di Kasir</span>
                        <span class="text-[10px] text-zinc-400 uppercase">Tunai / EDC</span>
                    </div>
                </button>
            </div>
            <button @click="$wire.showPaymentModal = false"
                class="w-full mt-6 text-zinc-400 font-bold text-xs uppercase">Batal</button>
        </div>
    </div>

    @if ($showQrModal)
        <div class="fixed inset-0 bg-black/90 z-[100] flex items-center justify-center p-6 animate-in fade-in">
            <div class="w-full max-w-sm bg-white p-8 rounded-[3rem] text-center">
                <h3 class="text-zinc-900 font-black text-xl mb-1">QResta</h3>
                <p class="text-xs text-zinc-400 mb-6 font-bold uppercase tracking-widest">Tunjukkan ke Kasir</p>
                <div class="w-48 h-48 mx-auto mb-6 bg-zinc-50 p-4 rounded-3xl">
                    {!! $qrCodeSvg !!}
                </div>
                <button wire:click="$set('showQrModal', false)"
                    class="px-8 py-3 bg-zinc-900 text-white rounded-2xl font-bold text-xs uppercase">Tutup</button>
            </div>
        </div>
    @endif

    <div x-data="{ show: false }" x-on:order-paid-success.window="show = true" x-show="show" x-cloak
        class="fixed inset-0 z-[200] flex items-center justify-center p-6 bg-brand-600">
        <div class="text-center text-white animate-in zoom-in-95 duration-500">
            <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h2 class="text-4xl font-black mb-2">Lunas!</h2>
            <p class="opacity-80 mb-8">Terima kasih atas pembayarannya.<br>Silahkan nikmati hidangan Anda.</p>
            <p class="text-[10px] font-bold uppercase tracking-widest bg-black/10 inline-block px-4 py-2 rounded-full">
                Sesi Selesai</p>
        </div>
    </div>
</div>
