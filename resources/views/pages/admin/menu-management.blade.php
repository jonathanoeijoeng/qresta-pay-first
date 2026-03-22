<?php

use App\Models\Branch;
use App\Models\Menu;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

new class extends Component {
    #[Url(history: true)]
    public $search = '';

    #[Url(history: true)]
    public $selectedBranch = '';

    #[Computed]
    public function branches()
    {
        return Branch::query()
            // 1. Filter Cabang jika dipilih
            ->when($this->selectedBranch, fn($q) => $q->where('id', $this->selectedBranch))
            ->with(['menus' => function ($q) {
                // 2. Filter Menu & Eager Load Kategori
                $q->when($this->search, fn($sq) => $sq->where('name', 'ilike', '%' . $this->search . '%'))
                  ->with('category')
                  ->orderBy('name', 'asc');
            }])
            ->orderBy('name', 'asc')
            ->get()
            // 3. Transformasi Data: Kelompokkan Menu per Kategori secara manual
            ->map(function ($branch) {
                $branch->grouped_menus = $branch->menus->groupBy(function ($menu) {
                    return $menu->category->name ?? 'Lainnya';
                });
                return $branch;
            })
            // 4. Buang Cabang yang tidak punya menu sesuai kriteria pencarian
            ->filter(fn($branch) => $branch->menus->isNotEmpty());
    }

    public function toggleActive($menuId)
    {
        $menu = Menu::findOrFail($menuId);
        $menu->is_available = !$menu->is_available;
        $menu->save();

        $this->dispatch('toast', type: 'success', text: "Menu {$menu->name} kini " . ($menu->is_available ? 'tersedia' : 'tidak sersedia') . '.');
        
        // Data akan otomatis refresh karena reaktivitas Livewire
    }
}; ?>

<div class="p-6 bg-slate-50 min-h-screen">
    {{-- Header Section --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-brand-dark tracking-tight">Manajemen Menu & Cabang</h1>
        <div class="text-sm text-slate-500 bg-brand-accent px-4 py-1.5 rounded-full border border-brand/10 font-medium">
            {{ now()->translatedFormat('d M Y') }}
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="flex flex-col md:flex-row gap-4 mb-10">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="Cari menu favorit..." variant="filled" clearable />
        </div>
        <div class="w-full md:w-72">
            <flux:select wire:model.live="selectedBranch" placeholder="Pilih Cabang">
                <flux:select.option value="">Semua Cabang</flux:select.option>
                @foreach(App\Models\Branch::orderBy('name')->get() as $b)
                <flux:select.option value="{{ $b->id }}">{{ $b->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    {{-- Data Display --}}
    <div class="space-y-12">
        @forelse($this->branches as $branch)
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-slate-200 transition-all">
            {{-- Cabang Header --}}
            <div class="bg-brand px-6 py-4 flex justify-between items-center text-white">
                <div>
                    <h2 class="text-xl font-bold tracking-tight">{{ $branch->name }}</h2>
                    <p class="text-orange-100 text-xs font-medium opacity-90">{{ $branch->location ?? 'Lokasi Belum
                        Diatur' }}</p>
                </div>
                <div class="bg-white/20 p-2 rounded-lg">
                    <flux:icon name="map-pin" variant="mini" class="text-white" />
                </div>
            </div>

            <div class="p-6">
                @foreach($branch->grouped_menus as $categoryName => $menus)
                <div class="mb-10 last:mb-0">
                    {{-- Kategori Label --}}
                    <h3 class="text-xs font-black text-brand uppercase tracking-[0.2em] mb-5 flex items-center gap-3">
                        <span class="w-2 h-2 bg-brand rounded-full shadow-[0_0_8px_rgba(237,142,74,0.5)]"></span>
                        {{ $categoryName }}
                        <span class="h-[1px] flex-1 bg-slate-100"></span>
                    </h3>

                    {{-- Menu Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($menus as $menu)
                        <div
                            class="group flex items-center justify-between p-4 rounded-xl border transition-all duration-200 {{ $menu->is_available ? 'bg-white border-slate-200 hover:border-brand/40 hover:shadow-md hover:shadow-brand/5' : 'bg-slate-50/80 border-slate-100 opacity-60 grayscale-[0.5]' }}">
                            <div class="flex-1 pr-3">
                                <p class="font-bold text-slate-800 group-hover:text-brand transition-colors truncate">
                                    {{ $menu->name }}
                                </p>
                                <p class="text-brand-dark font-mono text-sm mt-0.5">
                                    IDR {{ number_format($menu->price, 0, ',', '.') }}
                                </p>
                            </div>

                            <div class="flex items-center">
                                <x-toggle wire:click="toggleActive({{ $menu->id }})"
                                    :checked="(bool)$menu->is_available" color="orange" size="sm" />
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @empty
        <div
            class="flex flex-col items-center justify-center py-24 bg-white rounded-3xl border-2 border-dashed border-slate-200 text-slate-400">
            <div class="bg-slate-50 p-6 rounded-full mb-4">
                <flux:icon name="magnifying-glass" class="w-10 h-10 opacity-20" />
            </div>
            <p class="font-medium text-slate-500">Hasil tidak ditemukan</p>
            <p class="text-sm opacity-70">Coba gunakan kata kunci lain atau pilih cabang yang berbeda.</p>
        </div>
        @endforelse
    </div>
</div>