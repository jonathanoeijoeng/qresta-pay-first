<?php

use Livewire\Component;
use App\Models\Menu;
use App\Models\Category;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $sortField;
    public $sortDirection;
    
    public $search = '';
    public $filterCategory = '';
    public $filterStatus = '';

    public $editingMenu = null;
    public $name = '';
    public $category_id = '';
    public $description = '';
    public $base_price = '';
    public $is_active = true;
    public $existingImage = null;
    public $image;
    public $selectedImage = null;

    public function showImageModal($id)
    {
        $menu = Menu::findOrFail($id);
        $this->selectedImage = $menu->image;
    }

    public function closeImageModal()
    {
        $this->selectedImage = null;
    }

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

    public function edit($id)
    {
        $menu = Menu::findOrFail($id);

        $this->editingMenu = $id;
        $this->name = $menu->name;
        $this->category_id = $menu->category_id;
        $this->description = $menu->description;
        $this->base_price = $menu->base_price;
        $this->is_active = $menu->is_active;
        $this->existingImage = $menu->image;

        $this->dispatch('modal-open', name: 'edit-menu');
    }

    public function update()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'base_price' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:8096',
        ]);

        $menu = Menu::findOrFail($this->editingMenu);

        $imageUrl = $menu->image;

        if ($this->image) {
            Storage::disk('public')->delete('menu-images/' . $imageUrl);
            $storedPath = $this->image->store('menu-images', 'public');
            $imageUrl = basename($storedPath);
        }

        $menu->update([
            'name' => $this->name,
            'category_id' => $this->category_id,
            'description' => $this->description,
            'base_price' => $this->base_price,
            'is_active' => $this->is_active,
            'image' => $imageUrl,
        ]);

        $this->reset(['editingMenu', 'name', 'category_id', 'description', 'base_price', 'is_active', 'image']);
        $this->dispatch('modal-close', name: 'edit-menu');
        $this->dispatch('toast', type: 'success', text: 'Menu berhasil diperbarui.');
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
            'menus' => $menus,
            'categories' => Category::all(),
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
                <x-table.cell class="items-center cursor-pointer" wire:click="showImageModal({{ $menu->id }})"
                    x-on:click="$flux.modal('preview-image').show()">
                    <img src="{{ $menu->image ? asset('storage/menu-images/' . $menu->image) : asset('images/food.svg') }}"
                        alt="Menu Image" class="w-10 h-10 object-cover mx-auto block" />
                </x-table.cell>
                <x-table.cell class="text-center" showOnMobile="true">
                    <div class="flex justify-center gap-2">
                        <x-edit x-on:click="$flux.modal('edit-menu').show()" wire:click="edit({{ $menu->id }})" />
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

    <div x-data="{ isUploading: false, progress: 0 }" x-on:livewire-upload-start="isUploading = true"
        x-on:livewire-upload-finish="isUploading = false" x-on:livewire-upload-error="isUploading = false"
        x-on:livewire-upload-progress="progress = $event.detail.progress" class="mt-4">
        <flux:modal name="edit-menu" class="min-w-[500px]">
            <form wire:submit.prevent="update" class="space-y-4">
                <div>
                    <flux:heading size="lg">EDIT MENU</flux:heading>
                    <flux:subheading>Perbarui informasi menu dan harga.</flux:subheading>
                </div>
                <flux:input wire:model.defer="name" label="Nama Menu" placeholder="Masukkan nama menu" />
                <flux:select wire:model.defer="category_id" label="Kategori">
                    <flux:select.option value="">Pilih Kategori</flux:select.option>
                    @foreach($categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model.defer="base_price" type="number" label="Harga" placeholder="10000" />
                <flux:input wire:model.defer="description" label="Deskripsi" placeholder="Deskripsi singkat" />
                <div class="border-2 border-dashed rounded-xl p-6 text-center
                   hover:border-brand-400 transition cursor-pointer mb-4">
                    <input type="file" wire:model="image" class="hidden" id="imageInput">
                    <div onclick="document.getElementById('imageInput').click()">
                        <p class="text-sm text-gray-500">
                            Drag & drop or click to upload
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            JPG, PNG, PDF (max 8MB)
                        </p>
                    </div>
                </div>
                {{-- Progress --}}
                <div x-show="isUploading" class="mt-3">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full transition-all" :style="`width: ${progress}%`"></div>
                    </div>
                </div>
                {{-- Image Preview Logic --}}
                <div class="mt-4">
                    @if ($image)
                    {{-- Kondisi 1: User baru saja pilih file baru (Temporary Preview) --}}
                    <div class="relative inline-block">
                        @if(str_contains($image->getMimeType(), 'image'))
                        <img src="{{ $image->temporaryUrl() }}" class="rounded-lg max-h-48 border shadow-sm">
                        <span
                            class="absolute top-2 left-2 bg-blue-500 text-white text-[10px] px-2 py-0.5 rounded-full uppercase font-bold shadow">
                            New Upload
                        </span>
                        @else
                        <p class="text-sm text-zinc-600 flex items-center gap-2">
                            <flux:icon.document-text variant="outline" /> {{ $image->getClientOriginalName() }}
                        </p>
                        @endif
                    </div>

                    @elseif ($existingImage)
                    {{-- Kondisi 2: Belum ada upload baru, tapi ada gambar lama di database --}}
                    <div class="relative inline-block">
                        <img src="{{ Storage::disk('public')->url('menu-images/' . $existingImage) }}"
                            class="rounded-lg max-h-48 border shadow-sm grayscale-[20%] opacity-80">
                        <span
                            class="absolute top-2 left-2 bg-zinc-500 text-white text-[10px] px-2 py-0.5 rounded-full uppercase font-bold shadow">
                            Current Image
                        </span>
                    </div>
                    @endif
                </div>
                {{-- Error Message --}}
                @error('image')
                <div class=" flex items-center gap-2 text-sm text-red-500">
                    <span>⚠</span>
                    <span>{{ $message }}</span>
                </div>
                @enderror

                <x-toggle wire:model.defer="is_active" label="Aktif" />
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button variant="ghost" x-on:click="$flux.modal('edit-menu').close()">Batal</flux:button>
                    <flux:button type="submit" variant="primary" class="bg-brand-500 hover:bg-brand-600">Simpan
                        Perubahan
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    </div>

    {{-- Modal to show image --}}
    <flux:modal name="preview-image" class="min-w-[400px]">
        <div class="space-y-4">
            <flux:heading size="lg">Pratinjau Gambar</flux:heading>
            @if ($selectedImage)
            <img src="{{ Storage::disk('public')->url('menu-images/' . $selectedImage) }}" alt="Pratinjau Gambar Menu"
                class="w-full max-h-[400px] object-contain rounded" />
            @else
            <img src="{{ asset('images/food.svg') }}" alt="Pratinjau Gambar Menu"
                class="w-full max-h-[400px] object-contain rounded" />
            @endif
            <div class="flex justify-end">
                <flux:button variant="ghost" wire:click="closeImageModal()"
                    x-on:click="$flux.modal('preview-image').close()">Tutup</flux:button>
            </div>
        </div>
    </flux:modal>
</div>