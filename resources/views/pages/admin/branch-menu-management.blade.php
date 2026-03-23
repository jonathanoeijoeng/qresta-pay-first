<?php

use App\Models\Branch;
use App\Models\Menu;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\WithPagination;


new class extends Component {

    use WithPagination;

    public $sortField = 'branch';
    public $sortDirection = 'asc';

    public $filterBranch = '';
    public $filterMenu = '';
    public $filterCategory = '';
    public $filterStatus = '';

    public $search = '';
    public $selectedBranch = '';

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function getActiveFiltersCountProperty()
    {
        return collect([
            $this->search,
            $this->filterBranch,
        ])->filter()->count();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterBranch = '';
        $this->filterMenu = '';
        $this->filterCategory = '';
        $this->filterStatus = '';
        $this->resetPage();
    }
    
    public function render()
    {
        $columns = [
            'menus.id',
            'menus.name',
            'menus.category_id',
            'menus.image',
            'branches.id as branch_id',
            'branches.name as branch_name',
            'branches.code as branch_code',
            'branches.color as branch_color',
            'categories.name as category_name',
            'branch_menu.price as branch_price',
            'branch_menu.is_available as branch_is_available',
        ];

        // Cek berdasarkan permission, bukan role
        if (auth()->user()->can('base price')) {
            $columns[] = 'menus.base_price as base_price';
        }

        $query = Menu::query()
            // 1. Join ke tabel pivot dan branches
            ->join('branch_menu', 'menus.id', '=', 'branch_menu.menu_id')
            ->join('branches', 'branches.id', '=', 'branch_menu.branch_id')
            // 2. Join ke tabel categories agar bisa di-sort berdasarkan nama kategori
            ->join('categories', 'menus.category_id', '=', 'categories.id')
            ->select($columns)
            // Tetap gunakan with() jika Anda masih butuh objek category lengkap (misal untuk icon)
            ->with('category')

            // --- LOGIKA FILTER ---
            // 1. Filter Search (Menu & Cabang)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('menus.name', 'ilike', '%' . $this->search . '%')
                    ->orWhere('branches.name', 'ilike', '%' . $this->search . '%')
                    ->orWhere('branches.code', 'ilike', '%' . $this->search . '%')
                    ->orWhere('categories.name', 'ilike', '%' . $this->search . '%');
                });
            })

            // 2. Filter Cabang (Dropdown Specific)
            ->when($this->filterBranch, function ($query) {
                $query->where('branch_menu.branch_id', $this->filterBranch);
            })

            // 3. Filter Status (Tersedia/Sold Out)
            ->when($this->filterStatus !== '', function ($query) {
                $query->where('branch_menu.is_available', $this->filterStatus);
            })

            // 4. Filter Kategori (Jika masih dibutuhkan)
            ->when($this->filterCategory, function ($query) {
                $query->where('menus.category_id', $this->filterCategory);
            });

            // LOGIKA SORTING
            switch ($this->sortField) {
                case 'branch':
                    $query->orderBy('branches.name', $this->sortDirection)
                        ->orderBy('menus.name', 'asc'); // Secondary sort agar rapi
                    break;
                case 'category':
                    $query->orderBy('categories.name', $this->sortDirection);
                    break;
                case 'menu':
                    $query->orderBy('menus.name', $this->sortDirection);
                    break;
                case 'is_available':
                    // Diambil dari tabel pivot branch_menu
                    $query->orderBy('branch_menu.is_available', $this->sortDirection);
                    break;
                case 'price':
                    // Diambil dari tabel pivot branch_menu (harga spesifik cabang)
                    $query->orderBy('branch_price', $this->sortDirection); 
                    break;
                default:
                    // Default: Kelompokkan per Cabang dulu, lalu urut Nama Menu
                    $query->orderBy('branches.name', 'asc')
                        ->orderBy('menus.name', 'asc');
                    break;
            }

            $menus = $query->paginate(25);

        $listBranch = Branch::orderBy('name', 'asc')->get();
    
        return $this->view([
            'menus' => $menus,
            'listBranch' => $listBranch,
        ])->title('Branch Menu Management');
    }

    public function toggleAvailability($menuId, $branchId)
    {
        // 1. Ambil data menu
        $menu = Menu::findOrFail($menuId);

        // 2. Ambil data pivot saat ini untuk cabang spesifik tersebut
        $pivotData = $menu->branches()->where('branch_id', $branchId)->first()->pivot;
        
        // 3. Toggle statusnya
        $newStatus = !$pivotData->is_available;

        // 4. Update tabel pivot 'branch_menu'
        $menu->branches()->updateExistingPivot($branchId, [
            'is_available' => $newStatus
        ]);

        // 5. Kirim notifikasi (Toast)
        $branchName = \App\Models\Branch::find($branchId)->name;
        $statusText = $newStatus ? 'tersedia' : 'tidak tersedia';
        
        $this->dispatch('toast', 
            type: 'success', 
            text: "Status updated: {$menu->name} {$statusText} in {$branchName}."
        );
    }

}; 
?>

<div class="min-h-screen">
    <x-header header="Branch Menu Management"
        description="Manage menu items, adjust pricing per branch, and monitor real-time availability across all locations" />
    {{-- Search --}}
    <div class="block md:flex items-center justify-between mb-4">
        <div class="block md:flex items-center gap-3 mb-3 md:mb-0">
            <div class="mb-3 md:mb-0">
                <x-input name="search" type="text" wire:model.live.debounce.500ms="search"
                    placeholder="Search anything..." />
            </div>
            {{-- Category --}}
            <div class="mb-3 md:mb-0">
                <x-select name="branch" wire:model.live="filterBranch">
                    <option value="">Semua cabang</option>
                    @foreach($listBranch as $list)
                    <option value="{{ $list->id }}">{{ $list->name }} ({{ $list->code }})</option>
                    @endforeach
                </x-select>
            </div>
        </div>
        {{-- Reset --}}
        <button wire:click="resetFilters" @disabled($this->activeFiltersCount === 0) class="px-4 py-2 border
            rounded-lg
            bg-brand-300 text-brand-800 cursor-pointer shadow disabled:opacity-50
            disabled:cursor-not-allowed">
            Reset Filters
        </button>
    </div>

    <x-table :collection="$menus">
        <x-slot name="headers">
            <x-sort-header field="branch" label="Cabang" showOnMobile="true" :sortField="$sortField"
                :sortDirection="$sortDirection" />
            <x-sort-header field="category" label="Kategori" :sortField="$sortField" :sortDirection="$sortDirection" />
            <x-sort-header field="menu" label="Menu" showOnMobile="true" :sortField="$sortField"
                :sortDirection="$sortDirection" />
            <x-sort-header field="is_available" label="Status" showOnMobile="true" :sortField="$sortField"
                :sortDirection="$sortDirection" class="text-center" />
            @can('base price')
            <x-sort-header field="price" label="HPP" showOnMobile="true" :sortField="$sortField"
                :sortDirection="$sortDirection" class="text-right" />
            @endcan
            <x-sort-header field="price" label="Harga" showOnMobile="true" :sortField="$sortField"
                :sortDirection="$sortDirection" class="text-right" />
            <x-table.cell class="text-center">Image</x-table.cell>
            <x-table.cell class="text-center" showOnMobile="true">Aksi</x-table.cell>
        </x-slot>

        @forelse($menus as $menu)
        <x-table.row>
            <x-table.cell showOnMobile="true">
                <x-branch-badge :color="$menu->branch_color ?? '#71717a'" :code="$menu->branch_code ?? '??'"
                    :name="$menu->branch_name" />
            </x-table.cell>
            <x-table.cell>
                {{ $menu->category_name }}
            </x-table.cell>
            <x-table.cell showOnMobile="true">
                {{ $menu->name }}
            </x-table.cell>
            <x-table.cell showOnMobile="true">
                <x-toggle :checked="$menu->branch_is_available"
                    wire:click="toggleAvailability({{ $menu->id }}, {{ $menu->branch_id }})" />
            </x-table.cell>
            @can('base price')
            <x-table.cell class="text-right font-bold" showOnMobile="true">
                <span class="text-[10px] text-zinc-500 mr-1">IDR</span>
                {{ number_format($menu->branch_price, 0, '.', ',') }}
            </x-table.cell>
            @endcan
            <x-table.cell class="text-right font-bold" showOnMobile="true">
                <span class="text-[10px] text-zinc-500 mr-1">IDR</span>
                {{ number_format($menu->branch_price, 0, '.', ',') }}
            </x-table.cell>
            <x-table.cell class="items-center">
                <img src="{{ $menu->image ?? asset('images/food.svg') }}" alt="Menu Image"
                    class="w-10 h-10 object-cover mx-auto block" />
            </x-table.cell>
            <x-table.cell class="text-center" showOnMobile="true">
                <div class="flex justify-center gap-2">
                    <x-edit wire:click="edit({{ $menu->id }})" />
                    <x-delete wire:click="confirmDelete({{ $menu->id }})" />
                </div>
            </x-table.cell>
        </x-table.row>
        @empty
        <x-table.row>
            <x-table.cell colspan="5" class="py-10 text-center text-zinc-500">
                Belum ada menu yang terdaftar.
            </x-table.cell>
        </x-table.row>
        @endforelse
    </x-table>
</div>