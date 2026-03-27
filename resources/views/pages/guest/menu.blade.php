<?php

use Livewire\Component;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Table;
use App\Models\TableSession;
use App\Models\GlobalSetting;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

new class extends Component
{
    public $menus = [];
    public $cart = []; // Format: [menu_id => ['qty' => 1, 'notes' => '', 'price' => 15000]]
    public $search = '';
    public $branchName;
    public $branchId;
    public $tableNumber;
    public $cartCount = 0;
    public $cartTotal = 0;
    public $selectedMenu = null; // Menyimpan objek menu yang akan ditambah
    public $tempNote = '';      // Menyimpan input catatan sementara
    public bool $showNoteModal = false;


    public function mount()
    {
        // Proteksi: Jika tidak ada session meja, jangan tampilkan menu
        if (!session()->has('customer_table_id')) {
            return redirect()->route('invalid-access');
        }

        if(session('active_order_id')) {
            $sessionTableId = Order::find(session('active_order_id'))->table_id;
            if($sessionTableId !== session('customer_table_id')) {
                // dd('ID Meja Tidak Sesuai');
                session()->forget([
                    'active_order_id', 
                    'merging_order_id',
                ]);
            }

        } 

        $this->refreshCartSummary();
        $this->loadMenus();
    }

    public function cancelMerge() 
    {
        $orderNumber = Order::find(session('merging_order_id'))->order_number;
        session()->forget('merging_order_id');
        return redirect()->route('guest.order-status', $orderNumber);
    }

    public function openNoteModal($menuId)
    {
        $this->selectedMenu = Menu::find($menuId);
        $this->tempNote = ''; // Reset catatan setiap buka modal
        $this->showNoteModal = true;
    }
    
    public function loadMenus()
    {
        $table = Table::find(session('customer_table_id'));
        $this->branchId = $table->branch_id;      
        $this->tableNumber = $table->number;
        $branch = Branch::find($this->branchId);
        $this->branchName = $branch->name;
        $this->menus = $branch->menus()->wherePivot('is_available', true)->get();
    }

    public function removeFromCart($menuId)
    {
        if (isset($this->cart[$menuId])) {
            if ($this->cart[$menuId]['qty'] > 1) {
                $this->cart[$menuId]['qty']--;
            } else {
                unset($this->cart[$menuId]);
            }
        }
    }

    public function refreshCartSummary()
    {
        $orderId = session('active_order_id') ?? session('merging_order_id');

        if ($orderId) {
            $order = Order::with('items')->find($orderId);

            if ($order) {
                // Hitung jumlah item unik atau total quantity
                $this->cartCount = $order->items->sum('quantity');
                
                // Ambil subtotal (sebelum pajak) untuk tampilan ringkas
                $this->cartTotal = $order->items->sum('subtotal');
            } else {
                $this->cartCount = 0;
                $this->cartTotal = 0;
            }
        } else {
            $this->cartCount = 0;
            $this->cartTotal = 0;
        }
    }

    public function addToCartWithNote()
    {
        // Safety check: Pastikan menu terpilih ada
        if (!$this->selectedMenu) {
            $this->showNoteModal = false;
            return;
        }

        $tableId = session('customer_table_id');
        $mergingOrderId = session('merging_order_id');
        
        // --- KOREKSI LOGIKA DISINI ---
        if ($mergingOrderId) {
            $order = Order::find($mergingOrderId);
            
            // Jika order merging tidak valid/sudah dibayar, bersihkan session & buat draft
            if (!$order || $order->payment_status !== 'unpaid') {
                session()->forget('merging_order_id');
                $order = $this->getOrCreateDraftOrder($tableId);
            }
        } else {
            // Jika tidak merging, gunakan logic draft biasa
            $order = $this->getOrCreateDraftOrder($tableId);
        }

        // 1. Ambil harga terbaru
        $menuData = \App\Models\Menu::query()
            ->join('branch_menu', 'menus.id', '=', 'branch_menu.menu_id')
            ->where('menus.id', $this->selectedMenu->id)
            ->where('branch_menu.branch_id', $this->branchId)
            ->select('branch_menu.price')   
            ->first();

        if (!$menuData) return;
        $price = (int) $menuData->price;

        // Simpan active_order_id untuk kebutuhan floating cart/summary
        session(['active_order_id' => $order->id]);

        // 2. Tambah atau Update Item
        $existingItem = $order->items()
            ->where('menu_id', $this->selectedMenu->id)
            ->where('notes', $this->tempNote ?: null)
            ->where('status', 'draft')
            ->first();

        if ($existingItem) {
            $newQty = $existingItem->quantity + 1;
            $existingItem->update([
                'quantity' => $newQty,
                'subtotal' => $newQty * $existingItem->price_at_order
            ]);
        } else {
            $order->items()->create([
                'menu_id' => $this->selectedMenu->id,
                'quantity' => 1,
                'price_at_order' => $price,
                'subtotal' => $price,
                'notes' => $this->tempNote ?: null,
                'status' => 'draft'
            ]);
        }

        // 3. Update total_amount & reset status jika perlu
        // Jika order yang sudah 'completed' ditambah item baru, koki harus tahu
        $updateData = [
            'total_amount' => (int) $order->items()->sum('subtotal')
        ];
        
        if ($order->status === 'completed') {
            $updateData['status'] = 'pending'; // Reset agar koki lihat ada tambahan
        }

        $this->updateOrderTotals($order);

        // 4. Cleanup & Refresh
        $this->reset(['showNoteModal', 'selectedMenu', 'tempNote']);
        $this->refreshCartSummary();
    }

    /**
     * Helper untuk mendapatkan atau membuat Order Draft
     */
    private function getOrCreateDraftOrder($tableId)
    {
        $orderNumber = 'QRS-' . date('Ymd') . '-' . str_pad($tableId, 3, '0', STR_PAD_LEFT);
        
        return \App\Models\Order::firstOrCreate(
            [
                'branch_id' => $this->branchId,
                'table_id' => $tableId,
                'status' => 'draft',
                'payment_status' => 'unpaid'
            ],
            [
                'order_number' => $orderNumber,
                'total_amount' => 0,
                'tax_amount' => 0,
                'tax_percentage' => 11 // Misal 11%
            ]
        );
    }

    private function updateOrderTotals($order)
    {
        $subtotal = (int) $order->items()->sum('subtotal');
        // Jika tax_percentage Anda menggunakan bigInt/Decimal, sesuaikan rumusnya
        $tax = ($subtotal * $order->tax_percentage) / 100;

        $order->update([
            'total_amount' => $subtotal + (int)$tax,
            // Tambahkan tax_amount jika Anda punya kolomnya
        ]);
    }

    public function render()
    {
        $searchTerm = '%' . $this->search . '%';
        $branchId = $this->branchId;

            $categories = Category::query()
                // 1. FILTER KATEGORI: Hanya ambil kategori yang punya menu tersedia & cocok dengan search
                ->whereHas('menus', function ($q) use ($branchId, $searchTerm) {
                    $q->where('menus.name', 'ilike', $searchTerm)
                    ->whereHas('branches', function ($bq) use ($branchId) {
                        $bq->where('branch_id', $branchId)
                            ->where('branch_menu.is_available', true);
                    });
                })
                // 2. FILTER MENU: Ambil detail menu yang sesuai (untuk ditampilkan di dalam kategori)
                ->with(['menus' => function ($query) use ($branchId, $searchTerm) {
                    $query->select('menus.*', 'branch_menu.price as branch_price', 'branch_menu.is_available as branch_available')
                        ->join('branch_menu', 'menus.id', '=', 'branch_menu.menu_id')
                        ->where('branch_menu.branch_id', $branchId)
                        ->where('branch_menu.is_available', true)
                        ->where('menus.name', 'ilike', $searchTerm) // Filter search di sini juga
                        ->where('menus.is_active', true)
                        ->orderBy('menus.name', 'asc');
                }])
                // 3. SORTING KATEGORI (Food -> Beverage -> Snack)
                ->orderByRaw("
                    CASE 
                        WHEN name ILIKE 'food%' THEN 1 
                        WHEN name ILIKE 'beverage%' THEN 2 
                        WHEN name ILIKE 'snack%' THEN 3 
                        ELSE 4 
                    END ASC
                ")
                ->get();

        return $this->view([
            'categories' => $categories
        ])->layout('components.layouts.guest');
    }
};
?>

<div>
    {{-- Main Menu --}}
    <main class="p-4 space-y-4 pb-32">
        <div class="space-y-6">
            <header class="flex flex-col items-center justify-center pt-4 pb-2">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('logo.svg') }}" alt="Logo Qresta" />
                    <div class="font-black tracking-tight text-zinc-900 dark:text-zinc-50 uppercase text-2xl">
                        QRESTA <span class="font-normal">{{ $branchName }}</span>
                    </div>
                </div>
                <flux:badge color="zinc" inset="top" class="mt-1">
                    Meja #{{ $tableNumber }}
                </flux:badge>
            </header>
            @if(session('merging_order_id'))
            @php
            // Ambil data order dari database berdasarkan session
            $mergingOrder = \App\Models\Order::find(session('merging_order_id'));
            @endphp
            <div
                class="bg-brand-50 border border-brand-200 rounded-2xl p-3 flex items-center justify-between shadow-sm animate-in fade-in slide-in-from-top-2">
                <div class="flex items-center gap-2">
                    <div class="bg-brand-500 p-1.5 rounded-full">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] text-brand-600 font-black uppercase tracking-widest leading-none">Mode
                            Tambah Pesanan</span>
                        <span class="text-[11px] text-brand-900 font-bold leading-tight">Order {{
                            $mergingOrder->order_number }}</span>
                    </div>
                </div>
                <button wire:click="cancelMerge"
                    class="bg-white px-3 py-1.5 rounded-xl text-[10px] font-black text-brand-600 border border-brand-200 active:scale-95 transition-all shadow-sm">
                    BATALKAN
                </button>
            </div>
            @endif
        </div>
        {{-- Search Bar --}}
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari menu favorit..."
            icon="magnifying-glass" />
        {{-- Loop Kategori --}}
        @foreach($categories as $category)
        <section>
            <flux:heading size="lg"
                class="mt-8 mb-4 bg-brand-200 dark:bg-brand-500 px-2 py-2 font-bold text-zinc-900 border-b border-zinc-200 rounded-lg">
                {{ $category->name }}
            </flux:heading>

            <div class="grid grid-cols-1 gap-4">
                @forelse($category->menus as $menu)
                <div
                    class="bg-white dark:bg-brand-200 rounded-xl shadow-sm border border-zinc-100 flex overflow-hidden h-28">
                    {{-- Foto --}}
                    <div class="w-24 bg-zinc-100 flex items-center justify-center   ">
                        <img src="{{ $menu->image ? Storage::disk('public')->url('menu-images/' . $menu->image) : asset('images/food.svg') }}"
                            class="w-20 h-auto object-cover">
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 p-3 flex flex-col justify-between">
                        <div>
                            <h4 class="font-bold text-sm text-zinc-900 leading-tight">{{ $menu->name }}</h4>
                            <p class="text-[10px] text-zinc-500 line-clamp-2 mt-0.5">{{ $menu->description }}</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-xs text-brand-500">
                                IDR {{ number_format($menu->branch_price, 0, '.', ',') }}
                            </span>
                            <button wire:click="openNoteModal({{ $menu->id }})" wire:loading.attr="disabled" {{-- Saat
                                loading, ganti bg-zinc-900 menjadi bg-brand-500 --}} wire:loading.class="!bg-brand-500"
                                class="relative inline-flex items-center justify-center w-8 h-8 rounded-full bg-brand-500 text-white hover:bg-brand-600 active:scale-95 transition-all shadow-sm focus:outline-none">
                                {{-- Icon Plus: Sembunyikan saat loading --}}
                                <svg wire:loading.remove wire:target="openNoteModal({{ $menu->id }})    "
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15">
                                    </path>
                                </svg>

                                {{-- Spinner: Tampilkan saat loading (Tanpa BG lagi karena sudah dihandle parent) --}}
                                <div wire:loading wire:target="openNoteModal({{ $menu->id }})   "
                                    class="flex items-center justify-center">
                                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
                @empty
                <img src="{{ asset('images/404.svg') }}" alt="Empty" class="mx-auto">
                @endforelse
            </div>
        </section>
        @endforeach

        {{-- Empty State --}}
        @if($categories->isEmpty())
        <div class="py-12 text-center">
            <flux:icon.magnifying-glass class="mx-auto h-12 w-12 text-zinc-300" />
            <p class="mt-2 text-sm text-zinc-500">Menu "{{ $this->search }}" tidak ditemukan.</p>
        </div>
        @endif
    </main>
    {{-- Floating Cart Summary (Hanya tampil jika keranjang terisi) --}}
    @if($cartCount > 0)
    <div class="fixed bottom-10 left-0 right-0 px-6 z-[9999] animate-in fade-in slide-in-from-bottom-5">
        <div
            class="bg-zinc-900 dark:bg-zinc-700 text-white p-4 rounded-2xl shadow-2xl flex items-center justify-between border border-white/5">
            <div class="flex items-center gap-3">
                <div
                    class="bg-brand-500 text-white w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-black">
                    {{ $cartCount }}
                </div>
                <div class="flex flex-col">
                    <span class="text-[9px] text-zinc-400 uppercase font-bold">Total Pesanan</span>
                    <span class="font-bold text-sm">IDR {{ number_format($cartTotal, 0, '.', ',') }}</span>
                </div>
            </div>
            <a href="{{ route('guest.cart') }}"
                class="bg-brand-500 px-4 py-2 rounded-xl text-xs font-bold uppercase active:scale-95 transition-all">
                Review Order
            </a>
        </div>
    </div>
    @endif
    @if($showNoteModal)
    <div class="fixed inset-0 bg-black/50 z-[10000] flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white dark:bg-zinc-300 w-full max-w-md rounded-t-3xl sm:rounded-3xl p-6 
            shadow-2xl animate-in slide-in-from-bottom duration-300 
            overflow-x-hidden border-none outline-none">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-xl font-bold text-zinc-900">{{ $selectedMenu->name }}</h3>
                    <p class="text-sm text-zinc-500">Tambahkan catatan untuk menu ini</p>
                </div>
                <button wire:click="$set('showNoteModal', false)" class="text-zinc-400 p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <textarea wire:model="tempNote" placeholder="Contoh: Tidak pakai sambal, ekstra bawang goreng..."
                class="w-full bg-zinc-100 text-black border-none rounded-2xl p-4 text-base focus:ring-2 focus:ring-brand-500 h-32 resize-none"></textarea>

            <div class="mt-6 flex gap-3">
                <button wire:click="$set('showNoteModal', false)" class="flex-1 py-4 text-zinc-500 font-bold">
                    Batal
                </button>
                <button wire:click="addToCartWithNote"
                    class="flex-[2] bg-brand-500 text-white py-4 rounded-2xl font-bold shadow-lg shadow-brand-500/30 active:scale-95 transition-all">
                    Tambah ke Pesanan
                </button>
            </div>
        </div>
    </div>
    @endif
</div>