<?php

use Livewire\Component;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use App\Models\Branch;
use App\Models\Table;
use App\Models\Order;
use App\Models\TableSession;
use Illuminate\Support\Facades\Auth;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage; // Tambahkan ini
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

new class extends Component {
    public $qrCodeRawSvg;
    public $branchName;
    public $tableNumber;
    public $table_id; // Kita pakai ID Meja agar lebih akurat
    public $branch_id;
    public $token;
    public $url;

    public function mount()
    {
        if (Auth::user()->branch_id) {
            $this->branch_id = Auth::user()->branch_id;
            $this->branchName = Auth::user()->branch->name;
        } else {
            // Jika Super Admin (branch_id biasanya null)
            $this->branch_id = null;
            $this->branchName = 'Semua Cabang (Super Admin)';
        }
    }

    private function printToThermal($url, $tableNumber, $branchName)
    {
        try {
            $connector = new NetworkPrintConnector('192.168.1.100', 9100);
            $printer = new Printer($connector);

            /* 1. CETAK LOGO */
            $printer->feed();
            $logoPath = storage_path('app/public/qresta-300.png');
            if (file_exists($logoPath)) {
                $logo = EscposImage::load($logoPath);
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                // Pilih salah satu method di bawah sesuai kecocokan printer anda
                $printer->bitImage($logo);
                // $printer->graphics($logo); // Gunakan ini jika bitImage tidak muncul
                $printer->feed();
            }

            /* 2. NAMA CABANG & DETAIL */
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text('cabang ' . $branchName . "\n");
            $printer->text("--------------------------------\n");

            /* 3. NOMOR MEJA */
            $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH | Printer::MODE_DOUBLE_HEIGHT);
            $printer->text("MEJA $tableNumber\n");
            $printer->feed();

            /* 4. QR CODE */
            $printer->qrCode($url, Printer::QR_ECLEVEL_L, 8);
            $printer->feed();

            /* 5. FOOTER */
            $printer->selectPrintMode();
            $printer->text("Scan untuk mulai memesan\n");
            $printer->text(now()->format('d/m/Y H:i') . "\n");
            $printer->feed(6);

            $printer->cut();
            $printer->close();
        } catch (\Exception $e) {
            \Log::error('Gagal cetak Logo/QR: ' . $e->getMessage());
        }
    }

    public function generate()
    {
        $this->validate([
            'branch_id' => 'required|exists:branches,id',
            'table_id' => 'required|exists:tables,id',
        ]);

        // 1. CEK APAKAH ADA ORDER YANG BELUM LUNAS DI MEJA INI
        $hasUnpaidOrder = Order::where('table_id', $this->table_id)->where('payment_status', 'unpaid')->exists();

        if ($hasUnpaidOrder) {
            $this->dispatch('toast', type: 'error', text: 'Meja ini masih memiliki pesanan yang belum dibayar!');
            return; // Hentikan proses jika masih ada hutang
        }

        $activeSession = TableSession::where('table_id', $this->table_id)->where('status', 'active')->first();

        if (!$activeSession) {
            $this->token = Str::random(16);
            $activeSession = TableSession::create([
                'table_id' => $this->table_id,
                'token' => $this->token,
                'started_at' => now(),
                'status' => 'active',
            ]);
        } else {
            // Jika sudah ada sesi aktif, pakai token yang sudah ada
            $this->token = $activeSession->token;
        }

        // 2. GENERATE URL
        $this->url = url("/s/{$this->token}");

        // 3. GENERATE QR CODE
        $renderer = new ImageRenderer(new RendererStyle(400), new SvgImageBackEnd());
        $writer = new Writer($renderer);

        $this->qrCodeRawSvg = $writer->writeString($this->url);

        // Ambil data untuk tampilan label
        $table = Table::find($this->table_id);
        $this->branchName = Branch::find($this->branch_id)->name;
        $this->tableNumber = $table->number;

        $this->printToThermal($this->url, $this->tableNumber, $this->branchName);
        $this->dispatch('toast', type: 'success', text: 'QR Code Sesi Aktif Berhasil Dimuat!');
    }

    public function render()
    {
        return $this->view([
            'branches' => \App\Models\Branch::all(),
            // Ambil meja hanya jika branch_id sudah terisi
            'tables' => $this->branch_id ? \App\Models\Table::where('branch_id', $this->branch_id)->get() : [],
        ])->title('Generate QR Code');
    }
};
?>

<div class="space-y-6">
    <x-header header="QR Code Manager" description="Generate sesi meja baru untuk tamu" />

    <div class="p-6 bg-white rounded-2xl border border-zinc-200 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

            {{-- Sisi Kiri: Kendali Sesi --}}
            <div class="space-y-6">
                <div class="p-4 bg-zinc-50 rounded-xl border border-zinc-100 space-y-4">
                    <h3 class="text-sm font-bold text-zinc-700 flex items-center gap-2">
                        Konfigurasi Sesi
                    </h3>

                    <div>
                        <x-select label="Pilih Cabang" name="branch_id" wire:model.live="branch_id" :disabled="!Auth::user()->can('manage-branches')">
                            <option value="">-- Pilih Cabang --</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
                            @endforeach
                        </x-select>
                    </div>

                    {{-- Dropdown Meja (Muncul jika Cabang sudah dipilih) --}}
                    <flux:select wire:model="table_id" label="Pilih Meja">
                        <flux:select.option value="">-- Pilih Nomor Meja --</flux:select.option>
                        @foreach ($tables as $table)
                            <flux:select.option value="{{ $table->id }}">
                                Meja {{ $table->number }}
                                {{-- Anda bisa tambah badge status di sini nanti --}}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:button wire:click="generate" variant="primary" icon="qr-code"
                    class="w-full md:w-fit shadow-lg shadow-brand-500/20 bg-brand-500 hover:bg-brand-600 transition-all active:scale-95">
                    Generate & Buka Sesi
                </flux:button>
            </div>

            {{-- Sisi Kanan: Preview QR (Print Area) --}}
            <div class="flex flex-col items-center">
                <div id="printable-qr"
                    class="print-area p-8 bg-white rounded-3xl flex flex-col items-center min-h-[350px] justify-center w-full max-w-[300px]
                    {{ $qrCodeRawSvg ? 'border-0' : 'border-2 border-dashed border-zinc-200' }}">
                    @if ($qrCodeRawSvg)
                        <div class="flex flex-col items-center border border-zinc-100 rounded-3xl">
                            <div class="bg-white mb-2">
                                {!! $qrCodeRawSvg !!}
                            </div>
                            <div class="text-center space-y-1 mb-8">
                                <h2 class="text-xl font-black text-zinc-900 tracking-tight">QResta</h2>
                                <p class="text-xs font-medium text-zinc-500 uppercase tracking-widest">
                                    {{ $branchName }}
                                </p>
                                <div class="mt-4 py-1 px-3 bg-zinc-900 text-white rounded-lg inline-block">
                                    <span class="text-sm font-bold">MEJA {{ $tableNumber }}</span>
                                </div>
                                <p class="text-[10px] text-zinc-400 mt-4 font-mono">{{ $url }}</p>
                            </div>
                        </div>
                    @else
                        <div class="text-center text-zinc-300">
                            <flux:icon.qr-code class="w-16 h-16 mx-auto mb-3 opacity-20" />
                            <p class="text-sm">Pilih meja untuk<br>generate QR Code</p>
                        </div>
                    @endif
                </div>
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
