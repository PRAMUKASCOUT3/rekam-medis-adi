<?php

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

new class extends Component {
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
};
?>

<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                <i class="bi bi-people-fill text-blue-600 mr-2"></i>Manajemen User
            </h1>
            <p class="mt-1 text-sm text-gray-500">Kelola akun pengguna &amp; hak akses sistem</p>
        </div>
        <button type="button" wire:click="creating"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-elevation-sm hover:shadow-elevation-md transition-all">
            <i class="bi bi-plus-lg"></i>
            Tambah User
        </button>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
        <!-- Total Users -->
        <div class="bg-white rounded-2xl p-5 shadow-elevation-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="p-3 rounded-xl bg-blue-50">
                    <i class="bi bi-people-fill text-2xl text-blue-600"></i>
                </div>
                <span class="text-xs text-gray-400 font-medium">Total</span>
            </div>
            <p class="mt-4 text-sm text-gray-500 font-medium">Semua User</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $this->totalUsers() }}</p>
        </div>

        <!-- Admin -->
        <div class="bg-white rounded-2xl p-5 shadow-elevation-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="p-3 rounded-xl bg-purple-50">
                    <i class="bi bi-shield-fill text-2xl text-purple-600"></i>
                </div>
                <span class="text-xs text-gray-400 font-medium">Akses Penuh</span>
            </div>
            <p class="mt-4 text-sm text-gray-500 font-medium">Admin</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $this->adminCount() }}</p>
        </div>

        <!-- Regular User -->
        <div class="bg-white rounded-2xl p-5 shadow-elevation-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="p-3 rounded-xl bg-emerald-50">
                    <i class="bi bi-person-fill text-2xl text-emerald-600"></i>
                </div>
                <span class="text-xs text-gray-400 font-medium">User Bias</span>
            </div>
            <p class="mt-4 text-sm text-gray-500 font-medium">User</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $this->regularUserCount() }}</p>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 p-5 sm:p-6 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <!-- Search -->
            <div class="relative flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" wire:model.live.debounce="search" placeholder="Cari nama atau email user..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all placeholder:text-gray-400" />
            </div>

            <!-- Per Page -->
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-500 whitespace-nowrap">Per halaman:</label>
                <select wire:model="perPage"
                    class="px-3 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 overflow-hidden">
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px]">
                <thead>
                    <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50/80">
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('name')">
                            <div class="flex items-center gap-1.5">
                                <span>Nama</span>
                                @if ($sortColumn === 'name')
                                    <i
                                        class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-blue-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('email')">
                            <div class="flex items-center gap-1.5">
                                <span>Email</span>
                                @if ($sortColumn === 'email')
                                    <i
                                        class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-blue-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('role')">
                            <div class="flex items-center gap-1.5">
                                <span>Role</span>
                                @if ($sortColumn === 'role')
                                    <i
                                        class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-blue-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('created_at')">
                            <div class="flex items-center gap-1.5">
                                <span>Dibuat</span>
                                @if ($sortColumn === 'created_at')
                                    <i
                                        class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-blue-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-center px-6 py-3.5 font-semibold">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                        <tr class="table-row-hover hover:bg-gray-50/60 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow-elevation-sm shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 leading-tight">
                                            {{ $user->name }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">ID: {{ $user->id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <i class="bi bi-envelope text-gray-400"></i>
                                    <span class="max-w-[200px] truncate">{{ $user->email }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if ($user->role === 'admin')
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-purple-50 text-purple-700 text-xs font-semibold">
                                        <i class="bi bi-shield-fill-check text-[10px]"></i>Admin
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-semibold">
                                        <i class="bi bi-person-fill text-[10px]"></i>User
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-600">{{ $user->created_at->format('d M Y') }}</span>
                                    <span class="text-xs text-gray-400">{{ $user->created_at->format('H:i') }}
                                        WIB</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-1">
                                    <!-- Edit Button -->
                                    <button type="button" wire:click="editing({{ $user->id }})"
                                        class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors"
                                        title="Edit">
                                        <i class="bi bi-pencil-square text-lg"></i>
                                    </button>
                                    <!-- Delete Button -->
                                    @if (auth()->id() !== $user->id)
                                        <button type="button" wire:click="confirmDelete({{ $user->id }})"
                                            class="p-2 rounded-lg text-red-500 hover:bg-red-50 transition-colors"
                                            title="Hapus">
                                            <i class="bi bi-trash3 text-lg"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="p-4 rounded-2xl bg-gray-50">
                                        <i class="bi bi-person-x text-4xl text-gray-300"></i>
                                    </div>
                                    <p class="text-gray-500 font-medium">Tidak ada user ditemukan</p>
                                    <p class="text-sm text-gray-400">Coba kata kunci lain atau buat user baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($users->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
                <p class="text-sm text-gray-500">
                    Menampilkan
                    <span class="font-semibold text-gray-700">{{ $users->firstItem() ?? 0 }}</span>
                    –
                    <span class="font-semibold text-gray-700">{{ $users->lastItem() }}</span>
                    dari
                    <span class="font-semibold text-gray-700">{{ $users->total() }}</span>
                    user
                </p>
                <div class="flex gap-1">
                    {{ $users->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Create / Edit Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 transition-opacity duration-200
        {{ $showModal ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none' }}"
        wire:click="closeModal">

        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

        <!-- Modal Content -->
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-elevation-xl border border-gray-100 overflow-hidden
            transform transition-all duration-200
            {{ $showModal ? 'scale-100 translate-y-0' : 'scale-95 translate-y-4' }}"
            wire:click.stop>

            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        {{ $editMode ? 'Edit User' : 'Tambah User Baru' }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ $editMode ? 'Perbarui informasi akun user' : 'Isi data user dengan lengkap' }}
                    </p>
                </div>
                <button type="button" wire:click="closeModal"
                    class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                    <i class="bi bi-x-lg text-xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-5 space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="name">
                        Nama Lengkap <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" wire:model="name" placeholder="Masukkan nama lengkap"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" />
                    @error('name')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="email">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="email" wire:model="email" placeholder="user@example.com"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" />
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Role <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" wire:click="$set('role', 'admin')"
                            class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border-2 text-sm font-semibold transition-all
                                {{ $role === 'admin'
                                    ? 'border-purple-500 bg-purple-50 text-purple-700'
                                    : 'border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50' }}">
                            <i
                                class="bi bi-shield-fill {{ $role === 'admin' ? 'text-purple-600' : 'text-gray-400' }}"></i>
                            Admin
                        </button>
                        <button type="button" wire:click="$set('role', 'user')"
                            class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border-2 text-sm font-semibold transition-all
                                {{ $role === 'user'
                                    ? 'border-blue-500 bg-blue-50 text-blue-700'
                                    : 'border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50' }}">
                            <i
                                class="bi bi-person-fill {{ $role === 'user' ? 'text-blue-600' : 'text-gray-400' }}"></i>
                            User
                        </button>
                    </div>
                    @error('role')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="edit_password">
                        {{ $editMode ? 'Password Baru' : 'Password' }}
                        <span class="text-gray-400 font-normal">
                            ({{ $editMode ? 'kosongkan jika tidak diubah' : 'default: password' }})
                        </span>
                    </label>
                    <input type="password" id="edit_password" wire:model="edit_password" placeholder="••••••••"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" />
                    @error('edit_password')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3 bg-gray-50/50">
                <button type="button" wire:click="closeModal"
                    class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-colors">
                    Batal
                </button>
                <button type="button" wire:click="save"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-elevation-sm transition-all">
                    <i class="bi bi-check-lg"></i>
                    {{ $editMode ? 'Simpan Perubahan' : 'Tambah User' }}
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 transition-opacity duration-200
        {{ $showDeleteConfirm ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none' }}"
        wire:click="closeDeleteModal">

        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

        <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-elevation-xl border border-gray-100 p-6
            transform transition-all duration-200
            {{ $showDeleteConfirm ? 'scale-100 translate-y-0' : 'scale-95 translate-y-4' }}"
            wire:click.stop>

            <div class="flex flex-col items-center text-center gap-4">
                <!-- Icon -->
                <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center">
                    <i class="bi bi-exclamation-triangle-fill text-2xl text-red-500"></i>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-gray-900">Hapus User?</h3>
                    <p class="text-sm text-gray-500 mt-1">Tindakan ini tidak dapat dibatalkan. Data user akan
                        dihapus secara permanen dari sistem.</p>
                </div>

                <div class="flex items-center gap-3 w-full">
                    <button type="button" wire:click="closeDeleteModal"
                        class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                        Batal
                    </button>
                    <button type="button" wire:click="delete"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold transition-colors">
                        <i class="bi bi-trash3"></i>
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    @script
        <script>
            Livewire.on('toast', (event) => {
                const toast = document.createElement('div');
                const icon = event.type === 'success' ?
                    '<i class="bi bi-check-circle-fill text-emerald-500"></i>' :
                    event.type === 'warning' ?
                    '<i class="bi bi-exclamation-circle-fill text-amber-500"></i>' :
                    '<i class="bi bi-x-circle-fill text-red-500"></i>';

                toast.className =
                    `fixed bottom-6 right-6 z-[100] flex items-center gap-3 px-5 py-3 rounded-xl shadow-elevation-lg border border-gray-100 bg-white transition-all duration-300 translate-y-2 opacity-0`;
                toast.innerHTML = `
                ${icon}
                <span class="text-sm font-semibold text-gray-800">${event.message}</span>
            `;

                document.body.appendChild(toast);

                requestAnimationFrame(() => {
                    toast.classList.remove('translate-y-2', 'opacity-0');
                    toast.classList.add('translate-y-0', 'opacity-100');
                });

                setTimeout(() => {
                    toast.classList.remove('translate-y-0', 'opacity-100');
                    toast.classList.add('translate-y-2', 'opacity-0');
                    setTimeout(() => toast.remove(), 300);
                }, 3500);
            });
        </script>
    @endscript
</div>
