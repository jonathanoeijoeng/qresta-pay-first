<?php

use App\Models\User;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $name, $email, $password, $role = 'waitress';
    public $sortField = 'name';
    public $sortDirection = 'asc';

    public $editingUser = null; // Menyimpan ID user yang sedang diedit
    public $showEditModal = false;

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

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        $user->assignRole($this->role);

        $this->reset(['name', 'email', 'password', 'role']);
        $this->dispatch('toast', type: 'success', text: 'User berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $this->editingUser = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        
        // Ambil role pertama (asumsi 1 user 1 role di QResta)
        $this->role = $user->roles->first()?->name;

        $this->showEditModal = false;
    }

    public function update()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->editingUser,
            'role' => 'required'
        ]);

        $user = User::findOrFail($this->editingUser);
        $user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        // Update Role Spatie
        $user->syncRoles([$this->role]);

        $this->showEditModal = false;
        $this->reset(['name', 'email', 'role', 'editingUser']);
        $this->dispatch('modal-close', name: 'edit-user');
        
        $this->dispatch('toast', type: 'success', text: 'User berhasil diperbarui!');
    }

    public function render()
    {
        $query = User::query()
            ->select('users.*') // Penting agar ID user tidak tertukar saat join
            ->with('roles');

        switch ($this->sortField) {
            // case 'role_name': // Nama field yang kita kirim dari x-sort-header
            //     $query->leftJoin('model_has_roles', function ($join) {
            //             $join->on('users.id', '=', 'model_has_roles.model_id')
            //                 ->where('model_has_roles.model_type', '=', User::class);
            //         })
            //         ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id')
            //         ->orderBy('roles.name', $this->sortDirection);
            //     break;

            case 'name':
            case 'email':
                $query->orderBy($this->sortField, $this->sortDirection);
                break;

            default:
                $query->orderBy('users.created_at', 'desc');
                break;
        }

        $users = $query->paginate(10);
        return $this->view( [
            'users' => $users,
            'available_roles' => Role::all()
        ])->title('User Management');
    }
};
?>

<div>
    <x-header header="User Management" description="Manajemen user QResta" />

    <x-table :collection="$users">
        <x-slot name="headers">
            <x-sort-header field="name" label="Nama" :sortField="$sortField" :sortDirection="$sortDirection" />
            <x-sort-header field="email" label="Email" :sortField="$sortField" :sortDirection="$sortDirection" />
            <x-sort-header field="role_name" label="Role" :sortField="$sortField" :sortDirection="$sortDirection" />
            <x-table.cell>Action</x-table.cell>
        </x-slot>
        @forelse($users as $user)
        <x-table.row>
            <x-table.cell>{{ $user->name }}</x-table.cell>
            <x-table.cell>{{ $user->email }}</x-table.cell>
            <x-table.cell>
                <x-badge color="{{ $user->hasRole('admin') ? 'purple' : 'blue' }}">
                    {{ $user->getRoleNames()->first() }}
                </x-badge>
            </x-table.cell>
            <x-table.cell class="flex gap-2 items-center text-center">
                <x-edit x-on:click="$flux.modal('edit-user').show()" wire:click="edit({{ $user->id }})" />
                <x-delete :user="$user" />
            </x-table.cell>
        </x-table.row>
        @empty
        <x-table.row>
            <x-table.cell colspan="4" class="text-center">
                Tidak ada data staff.
            </x-table.cell>
        </x-table.row>
        @endforelse
    </x-table>

    <flux:modal name="edit-user" class="min-w-[400px]">
        <form wire:submit="update" class="space-y-6">
            <div>
                <flux:heading size="lg">Edit User</flux:heading>
                <flux:subheading>Perbarui informasi akun dan hak akses staff.</flux:subheading>
            </div>

            <flux:input wire:model="name" label="Nama Lengkap" placeholder="Masukkan nama..." />

            <flux:input wire:model="email" type="email" label="Alamat Email" placeholder="email@example.com" />

            <flux:select wire:model="role" label="Role / Jabatan">
                <x-slot name="icon">
                    <flux:icon.user-group variant="micro" />
                </x-slot>
                <flux:select.option value="">Pilih Role</flux:select.option>
                @foreach($available_roles as $roleItem)
                {{-- Kita gunakan name sebagai value karena syncRoles() butuh nama atau ID --}}
                <flux:select.option value="{{ $roleItem->name }}">
                    {{ ucwords(str_replace('_', ' ', $roleItem->name)) }}
                </flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" x-on:click="$flux.modal('edit-user').close()">Batal</flux:button>
                <flux:button type="submit" variant="primary" class="bg-brand-500 hover:bg-brand-600">
                    Simpan Perubahan
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>