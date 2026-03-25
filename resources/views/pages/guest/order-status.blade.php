<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Order;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer; 
use Livewire\Attributes\On; 

new class extends Component
{
    public $order;
    public $qrCodeSvg;

    public function mount($order_number)
    {
        $this->order = Order::with(['items.menu','table'])
            ->where('order_number', $order_number)
            ->firstOrFail();
        $this->generateQrCode();
    }

    public function getListeners()
    {
        // Mendengarkan channel order.{id}
        return [
            "echo:order.{$this->order->id},OrderUpdated" => 'refreshStatus',
        ];
    }

    public function refreshStatus()
    {
        $this->order->refresh();
        // Regenerate QR jika perlu, atau cukup refresh model
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
        return $this->view()->layout('components.layouts.guest');
    }
};
?>

<div class="max-w-md mx-auto min-h-screen bg-zinc-50 p-6">
    <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-orange-100 rounded-full mb-4">
            @if($order->status == 'pending')
            <div class="animate-bounce text-orange-600 text-3xl">⏳</div>
            @elseif($order->status == 'processing')
            <div class="animate-spin text-orange-600 text-3xl">🍳</div>
            @else
            <div class="text-green-600 text-3xl">✅</div>
            @endif
        </div>
        <h1 class="text-2xl font-black text-zinc-900 uppercase">Pesanan Diterima!</h1>
        <p class="text-zinc-500">Nomor Antrian: <span class="font-bold text-zinc-800">#{{ $order->order_number }}</span>
        </p>
        <div class="mt-2 flex justify-center">
            {!! $qrCodeSvg !!}
        </div>
    </div>

    <div class="bg-white rounded-3xl p-6 shadow-sm border border-zinc-100 mb-6">
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
                        class="w-6 h-6 rounded-full {{ $order->status == $step['id'] ? 'bg-orange-600 ring-4 ring-orange-100' : ($currentReached ? 'bg-orange-600' : 'bg-zinc-200') }}">
                    </div>
                    @if(!$loop->last) <div
                        class="w-0.5 h-10 {{ $currentReached && $order->status != $step['id'] ? 'bg-orange-600' : 'bg-zinc-200' }}">
                    </div> @endif
                </div>
                <div>
                    <h3
                        class="font-bold text-sm {{ $order->status == $step['id'] ? 'text-orange-600' : 'text-zinc-800' }}">
                        {{ $step['label'] }}</h3>
                    <p class="text-xs text-zinc-500">{{ $step['desc'] }}</p>
                </div>
            </div>
            @if($order->status == $step['id']) @php $currentReached = false; @endphp @endif
            @endforeach
        </div>
    </div>

    <div class="bg-zinc-900 text-white rounded-3xl p-6 shadow-xl">
        <h4 class="text-xs uppercase tracking-widest opacity-50 mb-4">Ringkasan Pesanan</h4>
        <div class="space-y-3 mb-4 border-b border-white/10 pb-4">
            @foreach($order->items as $item)
            <div class="flex justify-between text-sm">
                <span>{{ $item->quantity }}x {{ $item->menu->name }}</span>
                <span>IDR {{ number_format($item->subtotal, 0, ',', ',') }}</span>
            </div>
            @endforeach
        </div>
        <div class="flex justify-between font-bold text-lg">
            <span>Total Bayar</span>
            <span class="text-orange-400">IDR {{ number_format($order->total_amount, 0, ',', ',') }}</span>
        </div>
        <p class="text-[10px] opacity-50 mt-4 text-center italic">Silahkan tunjukkan halaman ini ke kasir saat melakukan
            pembayaran.</p>
    </div>

    <div class="mt-8 text-center">
        <a href="{{ route('guest.menu') }}"
            class="text-orange-600 font-bold text-sm border-b-2 border-orange-600 pb-1">Pesan Lagi?</a>
    </div>
</div>