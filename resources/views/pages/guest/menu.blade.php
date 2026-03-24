<?php

use Livewire\Component;
use App\Models\Menu;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $menus = [];
    public $cart = []; // Format: [menu_id => ['qty' => 1, 'notes' => '', 'price' => 15000]]
    public $search = '';

    public function mount()
    {
        // Proteksi: Jika tidak ada session meja, jangan tampilkan menu
        if (!session()->has('customer_table_id')) {
            return redirect()->route('invalid-access');
        }

        $this->loadMenus();
    }

    public function loadMenus()
    {
        $branchId = session('customer_branch_id');
        $branch = \App\Models\Branch::find($branchId);
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

    public function addToCart($menuId)
    {
        $menu = Menu::find($menuId);
        
        // Jika item sudah ada, tambah quantity, jika belum, buat baru
        if (isset($this->cart[$menuId])) {
            $this->cart[$menuId]['qty']++;
        } else {
            $this->cart[$menuId] = [
                'id' => $menu->id,
                'name' => $menu->name,
                'price' => $menu->price,
                'qty' => 1,
                'notes' => ''
            ];
        }
        
        $this->dispatch('toast', type: 'success', text: 'Ditambahkan ke keranjang');
    }

    public function getTotalProperty()
    {
        return collect($this->cart)->sum(fn($item) => $item['price'] * $item['qty']);
    }
};
?>

<div>
    {{-- Main Menu --}}
    <main class="p-4 space-y-4">
        {{-- Search Bar --}}
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari menu favorit..."
            icon="magnifying-glass" />

        <div class="grid grid-cols-1 gap-4">
            @foreach($menus as $menu)
            <div class="bg-white p-3 rounded-2xl flex items-center justify-between border border-zinc-100 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-zinc-100 rounded-xl overflow-hidden flex-shrink-0">
                        {{-- Placeholder jika gambar belum ada --}}
                        <div class="w-full h-full flex items-center justify-center text-zinc-400">
                            <flux:icon.cake />
                        </div>
                    </div>
                    <div>
                        <h3 class="font-bold text-zinc-800 leading-tight">{{ $menu->name }}</h3>
                        <p class="text-sm text-brand-500 font-semibold">IDR {{ number_format($menu->price, 0, ',', '.')
                            }}</p>
                    </div>
                </div>

                {{-- Add/Remove Logic --}}
                @if(isset($cart[$menu->id]))
                <div class="flex items-center gap-3 bg-zinc-100 rounded-full p-1">
                    <button wire:click="removeFromCart({{ $menu->id }})"
                        class="w-8 h-8 flex items-center justify-center bg-white rounded-full shadow-sm">-</button>
                    <span class="font-bold text-sm">{{ $cart[$menu->id]['qty'] }}</span>
                    <button wire:click="addToCart({{ $menu->id }})"
                        class="w-8 h-8 flex items-center justify-center bg-white rounded-full shadow-sm">+</button>
                </div>
                @else
                <button wire:click="addToCart({{ $menu->id }})"
                    class="bg-brand-500 text-white p-2 rounded-full shadow-md">
                    <flux:icon.plus class="w-5 h-5" />
                </button>
                @endif
            </div>
            @endforeach
        </div>
    </main>
    {{-- Floating Cart Summary (Hanya tampil jika keranjang terisi) --}}
    @if(count($cart) > 0)
    <div
        class="fixed bottom-6 left-4 right-4 bg-zinc-900 text-white p-4 rounded-2xl shadow-2xl flex justify-between items-center animate-bounce-subtle">
        <div>
            <p class="text-[10px] text-zinc-400 uppercase tracking-widest">Total Pesanan</p>
            <p class="text-lg font-bold">IDR {{ number_format($this->getTotal(), 0, ',', '.') }}</p>
        </div>
        <flux:button variant="primary" class="bg-[#E3833C] hover:bg-[#d17532] border-none">
            Lanjut Pesan
        </flux:button>
    </div>
    @endif
</div>