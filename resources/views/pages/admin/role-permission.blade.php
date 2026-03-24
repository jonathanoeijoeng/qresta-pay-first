<?php

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;
    
    public $sortField;
    public $sortDirection;
    public $permissionName = '';
    public $editId = '';

    public function edit($id)
    {
        $permission = Permission::findOrFail($id);
        $this->editId = $id;
        $this->permissionName = $permission->name;
        $this->dispatch('modal-open', name: 'create-permission');
    }
    
    public function storePermission()
    {
        $this->validate([
            'permissionName' => 'required|string|max:255|unique:roles,name',
        ]);

        if($id = $this->editId) {
            Permission::findOrFail($id)->update(['name' => $this->permissionName]);
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            $this->reset('permissionName');
            $this->dispatch('modal-close', 'create-permission');
            $this->dispatch('toast', type: 'success', text: 'Permission updated successfully.');
        } else {
            Permission::create(['name' => $this->permissionName, 'guard_name' => 'web']);
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            $this->reset('permissionName');
            $this->dispatch('modal-close', 'create-permission');
            $this->dispatch('toast', type: 'success', text: 'Permission created successfully.');
        }

    }

    public function render()
    {
        $roles = Role::with('permissions')->orderBy('name')->paginate(25);
        $permissions = Permission::orderBy('name')->get();

        return $this->view(compact('roles', 'permissions'))->title('Role & Permission Management');
    }
};
?>

<div>
    <x-header header="Role & Permission Management" description="Manage user roles and permissions" />
    <div class="block md:grid md:grid-cols-2 gap-4">
        <div>
            <x-button x-on:click="$flux.modal('create-role').show()" class="mb-4">Add New Role</x-button>
            <x-table :collection="$roles">
                <x-slot name="headers">
                    <x-table.cell showOnMobile="true">Role</x-table.cell>
                    <x-table.cell showOnMobile="true">Permission(s)</x-table.cell>
                    <x-table.cell class="text-center" showOnMobile="true">Action</x-table.cell>
                </x-slot>
                @forelse($roles as $role)
                <x-table.row>
                    <x-table.cell showOnMobile="true">
                        {{ $role->name }}
                    </x-table.cell>
                    <x-table.cell showOnMobile="true">
                        @foreach($role->permissions as $permission)
                        <x-badge color="orange">{{ $permission->name }}</x-badge>
                        @endforeach
                    </x-table.cell>
                    <x-table.cell class="text-center" showOnMobile="true">
                        <div class="flex justify-center gap-2">
                            <x-edit wire:click="edit({{ $role->id }})" />
                            <x-delete wire:click="confirmDelete({{ $role->id }})" />
                        </div>
                    </x-table.cell>
                </x-table.row>
                @empty
                <x-table.row>
                    <x-table.cell colspan="2" class="py-10 text-center text-zinc-500">
                        <x-nodatafound />
                    </x-table.cell>
                </x-table.row>
                @endforelse
            </x-table>
        </div>
        <div>
            <x-button x-on:click="$flux.modal('create-permission').show()" class="mb-4">Add New Permission</x-button>
            <x-table>
                <x-slot name="headers">
                    <x-table.cell showOnMobile="true">Permissions</x-table.cell>
                    <x-table.cell class="text-center" showOnMobile="true">Action</x-table.cell>
                </x-slot>
                @forelse($permissions as $permission)
                <x-table.row>
                    <x-table.cell showOnMobile="true">
                        {{ $permission->name }}
                    </x-table.cell>
                    <x-table.cell class="text-center" showOnMobile="true">
                        <div class="flex justify-center gap-2">
                            <x-edit wire:click="edit({{ $permission->id }})"
                                x-on:click="$flux.modal('create-permission').show()" />
                            <x-delete wire:click="confirmDelete({{ $permission->id }})" />
                        </div>
                    </x-table.cell>
                </x-table.row>
                @empty
                <x-table.row>
                    <x-table.cell colspan="2" class="py-10 text-center text-zinc-500">
                        <x-nodatafound />
                    </x-table.cell>
                </x-table.row>
                @endforelse
            </x-table>
        </div>
    </div>

    <flux:modal name="create-permission" class="min-w-[400px]">
        <form wire:submit="storePermission" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editId ? "Edit Permission" : "Create Permission" }}</flux:heading>
            </div>

            <flux:input wire:model="permissionName" label="Permission Name" placeholder="Masukkan nama..." />

            <x-button type="submit">Simpan</x-button>
        </form>
    </flux:modal>
</div>