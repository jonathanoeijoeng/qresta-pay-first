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
            // session()->forget(['active_order_id', 'customer_table_id', 'merging_order_id']);
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

<main class="p-6 space-y-6">
    {{-- STATUS PEMBAYARAN --}}
    @if ($order->payment_status == 'unpaid')
        {{-- Card Bayar (Hanya muncul jika belum bayar) --}}
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
    @else
        {{-- Badge LUNAS (Muncul jika sudah bayar) --}}
        <div
            class="bg-green-500 rounded-[2rem] p-6 text-white shadow-xl shadow-green-100 flex items-center justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest opacity-80">Status Pembayaran </p>
                <p class="font-bold">{{ $order->order_number }}</p>
                <h2 class="text-2xl font-black uppercase">Sudah Lunas</h2>
                <p class="text-[10px] opacity-80 font-bold mt-1">Terima kasih, pesanan sedang kami proses.</p>
            </div>
            <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>
    @endif
    <div class="relative flex justify-between items-start px-2">
        @php
            // Definisi step yang dinamis
            $steps = [
                ['id' => 'paid', 'icon' => '💰', 'label' => 'Paid'],
                ['id' => 'processing', 'icon' => '🍳', 'label' => 'Processed'],
                ['id' => 'completed-served', 'icon' => '🛎️', 'label' => 'Ready'],
            ];

            // Logika index: Jika belum bayar, index -1. Jika sudah bayar, cari posisinya.
            $statuses = ['paid', 'processing', 'completed-served'];
            $currentIndex = $order->payment_status == 'unpaid' ? -1 : array_search($order->status, $statuses);

            // Jika status order 'pending' tapi sudah bayar, kita anggap sudah di step 'paid' (index 0)
            if ($order->payment_status == 'paid' && $currentIndex === false) {
                $currentIndex = 0;
            }
        @endphp

        {{-- Progress Line --}}
        <div class="absolute top-4 left-0 w-full h-0.5 bg-zinc-100 dark:bg-zinc-700 -z-10">
            <div class="h-full bg-brand-500 transition-all duration-700 ease-in-out"
                style="width: {{ $currentIndex <= 0 ? '0' : $currentIndex * 50 }}%">
            </div>
        </div>

        @foreach ($steps as $index => $step)
            <div class="flex flex-col items-center gap-2">
                <div
                    class="w-8 h-8 rounded-full flex items-center justify-center text-sm transition-all duration-500
                    {{ $currentIndex >= $index ? 'bg-brand-600 text-white shadow-lg shadow-brand-200' : 'bg-zinc-100 text-zinc-400' }}">

                    @if ($currentIndex > $index)
                        {{-- Icon Checkmark jika sudah lewat --}}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                    @else
                        {{ $step['icon'] }}
                    @endif
                </div>
                <span
                    class="text-[10px] font-bold uppercase tracking-tight {{ $currentIndex == $index ? 'text-brand-600' : 'text-zinc-400' }}">
                    {{ $step['label'] }}
                </span>
            </div>
        @endforeach
    </div>

    {{-- DAFTAR MENU (Selalu Muncul) --}}
    <div class="bg-white dark:bg-zinc-800 rounded-[2rem] p-6 shadow-sm border border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-black text-zinc-800 dark:text-zinc-100 uppercase tracking-widest mb-4">Daftar Menu</h3>
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
                            <p class="text-[10px] text-zinc-400">@ IDR {{ number_format($item['price'], 0, '.', ',') }}
                            </p>
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

    @if ($order->payment_status === 'paid')
        <a href="{{ route('invoice.download', $order->order_number) }}"
            class="flex items-center justify-center gap-2 w-full py-3 bg-brand-600  rounded-2xl font-bold text-sm text-white shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                stroke="currentColor" class="w-4 h-4 text-zinc-50">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 12l4.5 4.5m0 0l4.5-4.5M12 3v13.5" />
            </svg>
            Simpan Invoice (PDF)
        </a>
    @endif

    {{-- TOMBOL ORDER LAGI (Hanya jika belum selesai dilayani) --}}
    @if ($order->status != 'completed-served')
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
    @endif
</main>
