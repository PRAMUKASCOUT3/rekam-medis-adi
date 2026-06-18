<?php

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

return new class extends Component {
    use WithPagination;

    // Form fields
    public string $name = '';
    public string $email = '';
    public string $role = 'user';
    public ?string $edit_password = null;

    // State
    public bool $showModal = false;
    public bool $editMode = false;
    public int|null $editingId = null;
    public bool $showDeleteConfirm = false;
    public int|null $deletingId = null;
    public string $search = '';
    public int $perPage = 10;
    public string $sortColumn = 'created_at';
    public string $sortDirection = 'desc';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $this->editMode ? 'unique:users,email,' . $this->editingId : 'unique:users,email'],
            'role' => ['required', 'in:admin,user'],
            'edit_password' => ['nullable', 'min:8'],
        ];
    }

    public function render()
    {
        $query = User::query();

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')->orWhere('email', 'like', '%' . $this->search . '%');
        }

        $users = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        return view('pages.users.index', [
            'users' => $users,
        ]);
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'email', 'role', 'edit_password', 'editMode', 'editingId', 'showDeleteConfirm', 'deletingId']);
        $this->role = 'user';
    }

    public function creating(): void
    {
        $this->resetForm();
        $this->editMode = false;
        $this->editingId = null;
        $this->showModal = true;
    }

    public function editing(int $id): void
    {
        $user = User::findOrFail($id);
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role ?: 'user';
        $this->edit_password = null;
        $this->editMode = true;
        $this->editingId = $id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if ($validated['edit_password']) {
            $data['password'] = Hash::make($validated['edit_password']);
        }

        if ($this->editMode && $this->editingId) {
            User::findOrFail($this->editingId)->update($data);
            $message = 'User berhasil diperbarui.';
        } else {
            if (!isset($data['password'])) {
                $data['password'] = Hash::make('password');
            }
            User::create($data);
            $message = 'User berhasil ditambahkan.';
        }

        $this->dispatch('toast', type: 'success', message: $message);
        $this->resetForm();
        $this->showModal = false;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            User::findOrFail($this->deletingId)->delete();
            $this->dispatch('toast', type: 'warning', message: 'User berhasil dihapus.');
        }
        $this->closeDeleteModal();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteConfirm = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteConfirm = false;
        $this->deletingId = null;
    }

    public function sortBy(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function totalUsers(): int
    {
        return User::count();
    }

    public function adminCount(): int
    {
        return User::where('role', 'admin')->count();
    }

    public function regularUserCount(): int
    {
        return User::where('role', 'user')->count();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    protected function view($data = [])
    {
        return app('view')->file('D:\laragon\www\rekam-medis\storage\framework/views/livewire/views/e0091d46.blade.php', $data);
    }
};
