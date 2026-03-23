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
    
    public function render()
    {
        $query = Menu::query()
            // 1. Ambil SEMUA kolom dari menus, tapi JANGAN ambil name dari branches secara otomatis
            ->select('menus.*') 
            // 2. Gunakan leftJoin untuk sorting
            ->leftJoin('branches', 'branches.id', '=', 'menus.branch_id')
            // 3. Eager Load agar relasi $menu->branch->name tetap jalan
            ->with('branch');

        switch ($this->sortField) {
            case 'branch':
                $query->orderBy('branches.name', $this->sortDirection);
                break;
            case 'menu':
                $query->orderBy('menus.name', $this->sortDirection);
                break;
            case 'is_available':
                $query->orderBy('menus.is_available', $this->sortDirection);
                break;
            case 'price':
                $query->orderBy('menus.price', $this->sortDirection);
                break;
            default:
                $query->orderBy('branches.name', 'asc');
                break;
        }

        $menus = $query->paginate(25);

        return $this->view([
            'menus' => $menus,
        ]);
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

<div class="min-h-screen">
    <x-table :collection="$menus">
        <x-slot name="headers">
            <x-sort-header field="branch" label="Cabang" :sortField="$sortField" :sortDirection="$sortDirection" />
            <x-sort-header field="menu" label="Menu" :sortField="$sortField" :sortDirection="$sortDirection" />
            <x-sort-header field="is_available" label="Status" :sortField="$sortField" :sortDirection="$sortDirection"
                class="text-center" />
            <x-sort-header field="price" label="Harga" :sortField="$sortField" :sortDirection="$sortDirection"
                class="text-right" />
            <th class="px-4 py-3 text-center">Aksi</th>
        </x-slot>

        @forelse($menus as $menu)
        <x-table.row>
            <x-table.cell>
                <x-branch-badge :branch="$menu->branch" />
            </x-table.cell>
            <x-table.cell class="font-medium text-zinc-900 dark:text-white">
                {{ $menu->name }}
            </x-table.cell>
            <x-table.cell class="text-center">
                {{-- Toggle ketersediaan menu --}}
                <x-toggle :checked="$menu->is_available" wire:click="toggleAvailability({{ $menu->id }})" />
            </x-table.cell>
            <x-table.cell class="text-right font-bold">
                <span class="text-[10px] text-zinc-500 mr-1">IDR</span>
                {{ number_format($menu->price, 0, '.', ',') }}
            </x-table.cell>
            <x-table.cell class="text-center">
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