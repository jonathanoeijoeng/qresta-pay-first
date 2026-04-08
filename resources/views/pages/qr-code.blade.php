<?php

use Livewire\Component;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Models\Branch;
use App\Models\Table;
use App\Models\TableSession;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $branch_id;
    public $branchName;

    public function mount()
    {
        // Default ke branch user login
        $this->branch_id = Auth::user()->branch_id;
        $this->updateBranchName();
    }

    public function updatedBranchId()
    {
        $this->updateBranchName();
    }

    private function updateBranchName()
    {
        $this->branchName = $this->branch_id ? Branch::find($this->branch_id)->name : 'Semua Cabang';
    }

    // Helper untuk generate SVG QR Code di Blade
    public function generateQrSvg($token)
    {
        $url = url("/s/{$token}");
        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());
        $writer = new Writer($renderer);
        return $writer->writeString($url);
    }

    public function render()
    {
        // 1. Logika untuk Dropdown Filter Branch
        $branches = Auth::user()->branch_id ? Branch::where('id', Auth::user()->branch_id)->get() : Branch::all();

        // 2. Logika untuk mengambil Data Meja
        $tablesQuery = Table::query();

        if ($this->branch_id) {
            // Jika ada filter branch yang terpilih
            $tablesQuery->where('branch_id', $this->branch_id);
        } elseif (Auth::user()->branch_id) {
            // Jika user adalah staff cabang dan belum ada filter, paksa ke branch dia
            $tablesQuery->where('branch_id', Auth::user()->branch_id);
        }

        return $this->view([
            'branches' => $branches,
            'tables' => $tablesQuery->get(),
        ])->title('Bulk QR Preview');
    }
};
?>

<div class="space-y-6">
    <div class="flex justify-between items-center no-print">
        <x-header header="Bulk QR Preview" description="Tampilan semua meja untuk uji coba scanning" />

        <div class="flex items-center gap-4">
            <div class="w-64">
                <x-select name="branch_id" wire:model.live="branch_id" {{-- Jika user punya branch_id, maka dropdown ini di-disable --}} :disabled="auth()->user()->branch_id ? true : false">
                    {{-- Jika Super Admin, beri opsi awal --}}
                    @if (!auth()->user()->branch_id)
                        <option value="">-- Pilih Cabang --</option>
                    @endif

                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </x-select>
            </div>
            <flux:button onclick="window.print()" variant="primary" icon="printer">
                Cetak Semua
            </flux:button>
        </div>
    </div>

    {{-- Grid Layout --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @forelse($tables as $table)
            <div
                class="bg-white p-6 rounded-3xl border border-zinc-200 shadow-sm flex flex-col items-center text-center hover:border-brand-500 transition-colors group">
                {{-- QR Code --}}
                <div class="mb-4">
                    {!! $this->generateQrSvg($table->qrcode_token) !!}
                </div>

                {{-- Label Meja --}}
                <h3 class="text-lg font-black text-zinc-900">MEJA {{ $table->number }}</h3>
                <p class="text-[10px] font-medium text-zinc-400 uppercase tracking-widest mb-2">{{ $branchName }}
                </p>

                <div class="px-3 py-1 bg-zinc-100 rounded-lg text-[9px] font-mono text-zinc-500 break-all">
                    {{ url("/s/{$table->qrcode_token}") }}
                </div>

                {{-- Link Klik (Hanya untuk testing di browser) --}}
                <a href="{{ url('/s/' . $table->qrcode_token) }}" target="_blank"
                    class="mt-4 text-xs text-brand-600 font-bold hover:underline no-print">
                    Buka Menu &rarr;
                </a>
            </div>
        @empty
            <div class="col-span-full py-20 text-center">
                <p class="text-zinc-500">Tidak ada meja di cabang ini.</p>
            </div>
        @endforelse
    </div>
</div>

<style>
    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: white !important;
        }

        .grid {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 20px !important;
        }

        .bg-white {
            border: 1px solid #eee !important;
            page-break-inside: avoid;
        }
    }
</style>
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
