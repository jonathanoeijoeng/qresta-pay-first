<?php

use Livewire\Component;
use App\Models\Menu;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $sortField;
    public $sortDirection;
    
    public $search = '';
    public $filterCategory = '';
    public $filterStatus = '';

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage(); // Sekarang ini akan berfungsi
    }
    
    public function toggleStatus($menuId)
    {
        $menu = Menu::find($menuId);
        $menu->is_active = !$menu->is_active;
        $menu->save();

        $message = $menu->is_active ? 'diaktifkan' : 'dinonaktifkan';

        $this->dispatch('toast', 
            type: 'success', 
            text: "Status updated: {$menu->name} {$message  }."
        );
    }
    
    public function render()
    {
        // 1. Definisikan kolom dasar (Menus & Categories)
        $columns = [
            'menus.id',
            'menus.name',
            'menus.category_id',
            'categories.name as category_name',
            'menus.base_price as base_price',
            'menus.image',
            'menus.is_active as is_active',
        ];

        // 3. Bangun Query
        $query = Menu::query()
            ->join('categories', 'menus.category_id', '=', 'categories.id')
            ->select($columns)
            ->with('category');

        // 4. Logika Filter (When) - Case Insensitive ILIKE
        $query->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('menus.name', 'ilike', '%' . $this->search . '%')
                        ->orWhere('categories.name', 'ilike', '%' . $this->search . '%');
                });
            })
            ->when($this->filterCategory, function ($q) {
                $q->where('menus.category_id', $this->filterCategory);
            });

        // 5. Logika Sorting (Switch Case)
        switch ($this->sortField) {
            case 'name':
                $query->orderBy('menus.name', $this->sortDirection);
                break;
            case 'category_name':
                $query->orderBy('categories.name', $this->sortDirection)
                ->orderBy('menus.name', 'asc');
                break;
            case 'is_active':
                $query->orderBy('menus.is_active', $this->sortDirection)
                ->orderBy('menus.name', 'asc');
                break;
            case 'base_price':
                // Jika kolom base_price tidak ada (bukan admin), tetap sort ke menus.price
                $query->orderBy('menus.base_price', $this->sortDirection)
                ->orderBy('menus.name', 'asc');
                break;
            default:
                $query->orderBy('categories.name', 'asc')
                ->orderBy('menus.name', 'asc');
                break;
        }

        $menus = $query->paginate(25);

        return $this->view([
            'menus' => $menus
        ])->title('Menu Management');
    }
};
?>

<div>
    <x-header header="Menu Management"
        description="Manage menu items, adjust base pricing to be used for all branches " />

    <div class="max-w-5xl">
        <x-table :collection="$menus">
            <x-slot name="headers">
                <x-sort-header field="category_name" label="Kategori" showOnMobile="true" :sortField="$sortField"
                    :sortDirection="$sortDirection" />
                <x-sort-header field="name" label="Menu" showOnMobile="true" :sortField="$sortField"
                    :sortDirection="$sortDirection" />
                <x-sort-header field="is_active" label="Status" showOnMobile="true" :sortField="$sortField"
                    :sortDirection="$sortDirection" class="text-center" />
                <x-sort-header field="base_price" label="Harga" showOnMobile="true" :sortField="$sortField"
                    :sortDirection="$sortDirection" class="text-right" />
                <x-table.cell class="text-center">Image</x-table.cell>
                <x-table.cell class="text-center" showOnMobile="true">Aksi</x-table.cell>
            </x-slot>
            @forelse($menus as $menu)
            <x-table.row>
                <x-table.cell showOnMobile="true">
                    {{ $menu->category_name }}
                </x-table.cell>
                <x-table.cell showOnMobile="true">
                    {{ $menu->name }}
                </x-table.cell>
                <x-table.cell class="text-center" showOnMobile="true">
                    <x-toggle :checked="$menu->is_active" wire:click="toggleStatus({{ $menu->id }})" />
                </x-table.cell>
                <x-table.cell class="text-right font-bold" showOnMobile="true">
                    <span class="text-[10px] text-zinc-500 mr-1">IDR</span>
                    {{ number_format($menu->base_price, 0, '.', ',') }}
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
                    <x-nodatafound />
                </x-table.cell>
            </x-table.row>
            @endforelse
        </x-table>
    </div>
</div>