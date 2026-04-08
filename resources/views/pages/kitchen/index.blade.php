<?php

namespace App\Livewire\Kitchen;

use App\Models\Order;
use App\Models\GlobalSetting;
use App\Models\OrderItem;
use App\Events\OrderUpdated;
use App\Events\OrderCompleted;
use App\Events\OrderSent;
use Livewire\Component;
use App\Concerns\HasNotification;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

new class extends Component {
    use HasNotification;

    public $order;
    public $tat_kitchen = 0;

    public function mount()
    {
        $this->tat_kitchen = GlobalSetting::where('key', 'tat_kitchen')->value('value');
    }

    public function getListeners()
    {
        $branchId = auth()->user()->branch_id;
        return [
            "echo-private:order-sent-branch.{$branchId},OrderSent" => 'handleNewOrder',
        ];

        $this->printToKitchen($order);

        // LOGIKA PRINT: Hanya jika LUNAS dan statusnya PENDING
        // if ($order->payment_status === 'paid' && $order->status === 'pending') {
        //     // Cek apakah order ini sudah pernah diprint sebelumnya (Opsional tapi disarankan)
        //     // Jika Anda belum punya kolom 'is_printed', abaikan pengecekan if ini
        //     if (!$order->is_printed) {
        //         try {
        //             $this->printToKitchen($order);

        //             // Tandai sudah diprint agar jika ada broadcast ulang tidak print lagi
        //             $order->update(['is_printed' => true]);
        //         } catch (\Exception $e) {
        //             \Log::error('Printer Kitchen Error: ' . $e->getMessage());
        //         }
        //     }
        // }
    }

    private function printToKitchen(Order $order)
    {
        $ip = '192.168.1.100'; // IP Printer Anda
        $port = 9100; // Port standar printer thermal network

        $connector = new NetworkPrintConnector($ip, $port, 3); // Timeout 3 detik
        $printer = new Printer($connector);

        try {
            // --- Header ---
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
            $printer->feed();
            $printer->text('TABLE #' . $order->table->number . "\n");
            $printer->selectPrintMode(); // Reset mode
            $printer->text('Order: ' . $order->order_number . "\n");
            $printer->text(now()->format('d/m/Y H:i') . "\n");
            $printer->text("--------------------------------\n");

            // --- Items ---
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            foreach ($order->items as $item) {
                // Format: Qty x Nama Menu
                $printer->setEmphasis(true);
                $printer->text($item->quantity . 'x ' . $item->menu->name . "\n");
                $printer->setEmphasis(false);

                // Notes (jika ada)
                if ($item->notes) {
                    $printer->text('  * ' . $item->notes . "\n");
                }
                $printer->text("\n"); // Spasi antar item
            }

            // --- Footer ---
            $printer->text("--------------------------------\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("KITCHEN COPY\n\n\n");
            $printer->feed();

            // Potong Kertas
            $printer->cut();
        } finally {
            $printer->close();
        }
    }

    public function handleNewOrder(Order $order)
    {
        $this->notifyAndRefresh('kitchen');
        if ($order->payment_status === 'paid' && $order->status === 'pending') {
            // $this->printToKitchen($order);
        }
    }

    public function refreshStatus()
    {
        $this->order->refresh();
    }

    public function render()
    {
        // Pay First: Hanya tampilkan pesanan yang SUDAH DIBAYAR
        $orders = Order::where('payment_status', 'paid')
            ->whereIn('status', ['pending', 'processing'])
            ->with([
                'table',
                'branch',
                'items' => function ($query) {
                    // 1. Ambil items yang belum matang/disajikan
                    $query
                        ->whereNotIn('status', ['cooked', 'served'])
                        ->with('menu') // Load relasi menu
                        ->join('menus', 'order_items.menu_id', '=', 'menus.id') // Join untuk sorting
                        ->select('order_items.*') // Pastikan hanya kolom order_items yang diambil agar tidak bentrok
                        ->orderBy('menus.name', 'asc'); // Urutkan berdasarkan nama menu
                },
            ])
            ->when(auth()->user()->branch_id, function ($query) {
                return $query->where('branch_id', auth()->user()->branch_id);
            })
            ->oldest() // Card tetap urut berdasarkan pesanan terlama (FIFO)
            ->get();

        // Filter manual: Hapus order yang tidak memiliki items (karena semua itemnya mungkin sudah cooked)
        $orders = $orders->filter(fn($order) => $order->items->isNotEmpty());

        $warningMinutes = GlobalSetting::where('key', 'kitchen_warning_time')->value('value') ?? 15;

        return $this->view([
            'orders' => $orders,
            'warningMinutes' => $warningMinutes,
        ])->title('Kitchen - Dashboard');
    }

    public function updateItemStatus($itemId, $newStatus)
    {
        $item = \App\Models\OrderItem::findOrFail($itemId);
        $item->update(['status' => $newStatus]);

        $order = $item->order->load('items');
        $allItems = $order->items;

        // Hitung jumlah berdasarkan status
        $totalItems = $allItems->count();
        $servedCount = $allItems->where('status', 'served')->count();
        $cookingCount = $allItems->where('status', 'cooking')->count();
        $processingCount = $allItems->where('status', 'processing')->count();

        // PENENTUAN STATUS ORDER (HEADER)
        if ($servedCount === $totalItems) {
            // SEMUA piring sudah di meja tamu
            $newOrderStatus = 'completed-served';
        } elseif ($cookingCount > 0 || $processingCount > 0 || $servedCount > 0) {
            // Ada yang lagi dimasak ATAU ada yang sudah diantar tapi belum semua
            $newOrderStatus = 'processing';
        } else {
            // Masih murni baru masuk semua
            $newOrderStatus = 'pending';
        }

        $order->update(['status' => $newOrderStatus]);

        broadcast(new OrderUpdated($order));
        broadcast(new OrderSent($order))->toOthers();
    }

    public function updateStatus($orderId, $newStatus)
    {
        $order = \App\Models\Order::with('items')->findOrFail($orderId);

        if ($newStatus === 'cooking') {
            // Mass update ke cooking
            $order
                ->items()
                ->whereIn('status', ['pending'])
                ->update(['status' => 'cooking']);
            $order->update(['status' => 'processing']);
        } elseif ($newStatus === 'served') {
            // Mass update ke served & selesaikan order
            $order
                ->items()
                ->where('status', '!=', 'served')
                ->update(['status' => 'served']);
            $order->update(['status' => 'completed-served']);
        }

        // Refresh data untuk broadcast agar data yang dikirim adalah yang terbaru setelah update
        $order->load('items');

        broadcast(new OrderUpdated($order))->toOthers();
        broadcast(new OrderSent($order))->toOthers(); // Dapur akan menerima update ini
    }
};
?>

<div>
    <x-header header="Kitchen" description="Cook fast Serve fast" />
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-2xl font-black uppercase tracking-widest">ORDER LIST</h1>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach ($orders as $order)
            <div class="bg-brand-50 border border-brand-600 rounded-3xl p-5 shadow-xl flex flex-col">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-4xl font-black text-zinc-800">#{{ $order->table->number }}</span>
                        <div class="text-xs mt-2 px-2 py-1 rounded-full  font-semibold"
                            style="background-color: {{ $order->branch->color }}; color: white;">
                            {{ $order->branch->code }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="px-3 py-1 bg-brand-300 rounded-full text-xs uppercase font-bold text-brand-800">
                            {{ $order->status }}
                        </div>

                    </div>
                </div>

                <div class="mb-6 flex-1">
                    <p class="text-zinc-400 text-[10px] uppercase font-bold tracking-widest mb-3">Item Details</p>
                    <ul class="space-y-3">
                        @forelse($order->items as $item)
                            <li
                                class="flex items-center justify-between p-4 rounded-xl {{ $item->status === 'served' ? 'bg-green-100/50 opacity-60' : 'bg-white shadow-sm' }}">
                                <div class="flex flex-col flex-1">
                                    <span class="text-sm font-black text-zinc-900">
                                        {{ $item->quantity }}x {{ $item->menu->name }}
                                    </span>
                                    @if ($item->notes)
                                        <span
                                            class="text-[10px] text-brand-600 italic leading-tight">"{{ $item->notes }}"</span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-1">
                                    @if ($item->status === 'pending')
                                        <button wire:click="updateItemStatus({{ $item->id }}, 'cooking')"
                                            class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                    d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z">
                                                </path>
                                            </svg>
                                        </button>
                                    @elseif($item->status === 'cooking')
                                        <button wire:click="updateItemStatus({{ $item->id }}, 'served')"
                                            class="p-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                    d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </button>
                                    @else
                                        <span class="p-1 text-green-600">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd"></path>
                                            </svg>
                                        </span>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="text-sm text-zinc-400 italic text-center py-4">No items</li>
                        @endforelse
                    </ul>
                </div>

                <div class="pt-4 border-t border-brand-200">
                    {{-- Tampilkan tombol Cook All HANYA jika ada item yang masih 'pending' --}}
                    @if ($order->items->where('status', 'pending')->isNotEmpty())
                        <button wire:click="updateStatus({{ $order->id }}, 'cooking')"
                            class="w-full bg-brand-500 hover:bg-brand-600 py-3 rounded-2xl tracking-widest font-black text-brand-50 uppercase text-xs transition-all mb-2">
                            Cook All
                        </button>
                    @else
                        {{-- Tampilkan tombol Serve All HANYA jika ada item yang statusnya 'cooking' --}}

                        <button wire:click="updateStatus({{ $order->id }}, 'served')"
                            class="w-full bg-green-600 hover:bg-green-700 py-3 rounded-2xl tracking-widest font-black text-brand-50 uppercase text-xs transition-all">
                            Serve All
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
