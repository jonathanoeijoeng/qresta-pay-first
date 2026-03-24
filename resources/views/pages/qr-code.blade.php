<?php

use Livewire\Component;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use App\Models\Branch;

new class extends Component
{
    public $qrCodeRawSvg;
    public $branchName;
    public $tableNumber;
    public $branch_id;


    public function generate()
    {
        $this->validate([
            'branch_id' => 'required|exists:branches,id',
            'tableNumber' => 'required|string|max:10',
        ]);

        // 1. Generate URL from ENV or config
        $baseUrl = config('app.url');
        $token = Str::random(16);
        
        $url = $baseUrl . '/qr-code?branch_id=' . $this->branch_id . '&tableNumber=' . $this->tableNumber . '&token=' . $token;

        // 2. Generate QR Code using BaconQrCode
        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $this->qrCodeRawSvg = $writer->writeString($url);
        $this->branchName = Branch::find($this->branch_id)->name;
    }

    public function render()
    {
        return $this->view([
            'branches' => Branch::all()
        ])->title('QR Code');
    }
};
?>

<div>
    <x-header header="QR Code" description="Generate QR Code" />
    <div class="p-6 bg-white rounded-xl border border-zinc-200">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Sisi Kiri: Form Input --}}
            <div class="space-y-4">
                <flux:select wire:model="branch_id" label="Pilih Cabang">
                    <flux:select.option value="">Pilih Cabang</flux:select.option>
                    @foreach($branches as $branch)
                    <flux:select.option value="{{ $branch->id }}">{{ $branch->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="tableNumber" label="Nomor Meja" placeholder="Contoh: 01, 12, atau A1" />

                <flux:button wire:click="generate" variant="primary"
                    class="w-fit text-white bg-brand-500 hover:bg-brand-600">
                    Generate QR Code
                </flux:button>
            </div>

            {{-- Sisi Kanan: Hasil QR --}}
            <div id="printable-qr" class="print-area p-8 bg-white border border-zinc-100 flex flex-col items-center">
                <div
                    class="flex flex-col items-center justify-center border-2 border-dashed border-zinc-100 rounded-xl p-4">
                    @if($qrCodeRawSvg)
                    <div class="bg-white p-4 rounded-lg mb-4">
                        {!! $qrCodeRawSvg !!}
                    </div>
                    <p class="font-bold text-zinc-700">QResta {{ $branchName }}</p>
                    <p class="text-sm text-zinc-700">Table: {{ $tableNumber }}</p>

                    @else
                    <div class="text-center text-zinc-400">
                        <flux:icon.qr-code class="w-12 h-12 mx-auto mb-2 opacity-20" />
                        <p class="text-xs">Isi data dan klik generate untuk melihat QR</p>
                    </div>
                    @endif
                </div>
            </div>
            <div class="flex gap-2 mt-4 no-print">
                <flux:button icon="printer" size="sm" onclick="window.print()">Print</flux:button>
            </div>
        </div>
    </div>
</div>
<style>
    @media print {

        /* Sembunyikan semua elemen di halaman */
        body * {
            visibility: hidden;
        }

        /* Tampilkan hanya area QR dan isinya */
        #printable-qr,
        #printable-qr * {
            visibility: visible;
        }

        /* Atur posisi area cetak agar di tengah kertas atau pojok kiri atas */
        #printable-qr {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            border: none;
            /* Hilangkan border saat cetak */
            padding: 0;
        }

        /* Sembunyikan tombol atau navigasi yang tidak perlu */
        .no-print {
            display: none !important;
        }
    }
</style>