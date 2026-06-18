<?php
use Livewire\Component;
use App\Models\RekamMedis;
use App\Models\Pasien;
use App\Models\Obat;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
?>

<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                <i class="bi bi-clipboard-heart-fill text-rose-600 mr-2"></i>Rekam Medis
            </h1>
            <p class="mt-1 text-sm text-gray-500">Kelola riwayat pemeriksaan &amp; diagnosa pasien</p>
        </div>
        <button type="button" wire:click="creating"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold shadow-elevation-sm hover:shadow-elevation-md transition-all">
            <i class="bi bi-plus-lg"></i>
            Tambah Rekam Medis
        </button>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
        <div class="bg-white rounded-2xl p-5 shadow-elevation-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="p-3 rounded-xl bg-blue-50">
                    <i class="bi bi-file-medical-fill text-2xl text-blue-600"></i>
                </div>
                <span class="text-xs text-gray-400 font-medium">Total</span>
            </div>
            <p class="mt-4 text-sm text-gray-500 font-medium">Semua Rekam Medis</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $this->totalRecords() }}</p>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-elevation-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="p-3 rounded-xl bg-emerald-50">
                    <i class="bi bi-calendar-check-fill text-2xl text-emerald-600"></i>
                </div>
                <span class="text-xs text-gray-400 font-medium">Hari Ini</span>
            </div>
            <p class="mt-4 text-sm text-gray-500 font-medium">Pemeriksaan Hari Ini</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $this->todayRecords() }}</p>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-elevation-sm border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="p-3 rounded-xl bg-purple-50">
                    <i class="bi bi-calendar3 text-2xl text-purple-600"></i>
                </div>
                <span class="text-xs text-gray-400 font-medium">Bulan Ini</span>
            </div>
            <p class="mt-4 text-sm text-gray-500 font-medium">Pemeriksaan Bulan Ini</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $this->monthRecords() }}</p>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 p-5 sm:p-6 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-lg"></i>
                <input type="text" wire:model.live.debounce="search"
                    placeholder="Cari No. RM, nama pasien, atau petugas..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all placeholder:text-gray-400" />
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-500 whitespace-nowrap">Per halaman:</label>
                <select wire:model="perPage"
                    class="px-3 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all cursor-pointer">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Records Table Card -->
    <div class="bg-white rounded-2xl shadow-elevation-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px]">
                <thead>
                    <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50/80">
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none"
                            wire:click="sortBy('nomor_rekam_medis')">
                            <div class="flex items-center gap-1.5">
                                <span>No. RM</span>
                                @if ($sortColumn === 'nomor_rekam_medis')
                                    <i
                                        class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-rose-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none" wire:click="sortBy('pasien_id')">
                            <div class="flex items-center gap-1.5">
                                <span>Pasien</span>
                                @if ($sortColumn === 'pasien_id')
                                    <i
                                        class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-rose-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5">Keluhan &amp; Diagnosa</th>
                        <th class="text-center px-6 py-3.5 font-semibold">Obat</th>
                        <th class="text-left px-6 py-3.5 cursor-pointer select-none"
                            wire:click="sortBy('tanggal_pemeriksaan')">
                            <div class="flex items-center gap-1.5">
                                <span>Tgl Pemeriksaan</span>
                                @if ($sortColumn === 'tanggal_pemeriksaan')
                                    <i
                                        class="bi bi-caret-{{ $sortDirection === 'asc' ? 'up-fill' : 'down-fill' }} text-rose-600"></i>
                                @else
                                    <i class="bi bi-caret-down text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="text-left px-6 py-3.5">Petugas</th>
                        <th class="text-center px-6 py-3.5 font-semibold">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($records as $record)
                        <tr class="table-row-hover hover:bg-gray-50/60 transition-colors">
                            <td class="px-6 py-4">
                                <span
                                    class="text-xs font-mono font-semibold text-rose-600 bg-rose-50 px-2.5 py-1 rounded-lg">{{ $record->nomor_rekam_medis }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <p class="text-sm font-semibold text-gray-900 leading-tight">
                                        {{ $record->pasien?->nama ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-400">ID: {{ $record->pasien_id }} &middot;
                                        {{ $record->pasien?->nik ?? '' }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1 max-w-[250px]">
                                    @if ($record->keluhan)
                                        <p class="text-xs text-gray-500 truncate" title="{{ $record->keluhan }}"><i
                                                class="bi bi-chat-left-quote text-gray-400 mr-1"></i>{{ Str::limit($record->keluhan, 40) }}
                                        </p>
                                    @endif
                                    @if ($record->diagnosa)
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-semibold self-start"><i
                                                class="bi bi-stethoscope text-[10px] mr-1"></i>{{ Str::limit($record->diagnosa, 40) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col items-center gap-1">
                                    @forelse($record->obats as $obatPivot)
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 text-xs font-semibold">
                                            <i class="bi bi-capsule text-[10px]"></i>
                                            {{ Str::limit($obatPivot->nama, 22) }}
                                            @if ($obatPivot->pivot?->jumlah > 1)
                                                <span
                                                    class="text-[10px] bg-indigo-100 text-indigo-600 px-1 rounded-full">x{{ $obatPivot->pivot->jumlah }}</span>
                                            @endif
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-400">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span
                                        class="text-sm text-gray-600">{{ $record->tanggal_pemeriksaan->format('d M Y') }}</span>
                                    <span class="text-xs text-gray-400">{{ $record->tanggal_pemeriksaan->format('H:i') }}
                                        WIB</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-gray-600">{{ $record->user?->name ?? 'N/A' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" wire:click="editing({{ $record->id }})"
                                        class="p-2 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors"
                                        title="Edit">
                                        <i class="bi bi-pencil-square text-lg"></i>
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $record->id }})"
                                        class="p-2 rounded-lg text-red-500 hover:bg-red-50 transition-colors"
                                        title="Hapus">
                                        <i class="bi bi-trash3 text-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="p-4 rounded-2xl bg-gray-50">
                                        <i class="bi bi-file-medical text-4xl text-gray-300"></i>
                                    </div>
                                    <p class="text-gray-500 font-medium">Tidak ada rekam medis ditemukan</p>
                                    <p class="text-sm text-gray-400">Coba kata kunci lain atau tambah rekam medis baru.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($records->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
                <p class="text-sm text-gray-500">
                    Menampilkan
                    <span class="font-semibold text-gray-700">{{ $records->firstItem() ?? 0 }}</span>
                    –
                    <span class="font-semibold text-gray-700">{{ $records->lastItem() }}</span>
                    dari
                    <span class="font-semibold text-gray-700">{{ $records->total() }}</span>
                    rekam medis
                </p>
                <div class="flex gap-1">
                    {{ $records->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Create / Edit Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 transition-opacity duration-200
        {{ $showModal ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none' }}"
        wire:click="closeModal">

        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

        <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-elevation-xl border border-gray-100 overflow-hidden
            transform transition-all duration-200
            {{ $showModal ? 'scale-100 translate-y-0' : 'scale-95 translate-y-4' }}"
            wire:click.stop>

            <!-- Modal Header -->
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">
                        {{ $editMode ? 'Edit Rekam Medis' : 'Tambah Rekam Medis Baru' }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ $editMode ? 'Perbarui data pemeriksaan pasien' : 'Input data pemeriksaan pasien' }}
                    </p>
                </div>
                <button type="button" wire:click="closeModal"
                    class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                    <i class="bi bi-x-lg text-xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-5 space-y-6 max-h-[70vh] overflow-y-auto">
                <!-- Row 1: No RM + Tgl Pemeriksaan + Petugas -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5"
                            for="nomor_rekam_medis">
                            No. Rekam Medis <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="nomor_rekam_medis" wire:model="nomor_rekam_medis"
                            placeholder="RM-001"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all"
                            disabled />
                        @error('nomor_rekam_medis')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="tanggal_pemeriksaan">
                            Tgl &amp; Waktu Pemeriksaan <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" id="tanggal_pemeriksaan" wire:model="tanggal_pemeriksaan"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all" />
                        @error('tanggal_pemeriksaan')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="user_id">
                            Petugas <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="user_id" value="{{ Auth::user()?->name ?? '' }}" readonly
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-100 focus:outline-none cursor-not-allowed" />
                        @error('user_id')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-100"></div>

                <!-- Row 2: Pasien + Obat -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- Pasien Search -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Pasien <span class="text-red-500">*</span>
                        </label>
                        <div class="relative" @click.away.stop="blurPasienSearch">
                            <input type="text" wire:model.live.debounce="pasien_search"
                                wire:focus="focusPasienSearch" wire:blur="blurPasienSearch"
                                wire:keydown.escape="clearPasienSearch" placeholder="Ketik nama atau NIK pasien..."
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all" />
                            @if ($pasien_focused && !$pasien_id)
                                <div
                                    class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-elevation-lg max-h-56 overflow-y-auto">
                                    @forelse($filteredPasiens as $p)
                                        <button type="button"
                                            wire:click="selectPasien({{ $p->id }}, '{{ addslashes($p->nama) }}')"
                                            class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-rose-50 transition-colors">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 font-bold text-xs shrink-0">
                                                {{ strtoupper(substr($p->nama, 0, 2)) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900">{{ $p->nama }}</p>
                                                <p class="text-xs text-gray-400">{{ $p->nik ?? 'Tanpa NIK' }}</p>
                                            </div>
                                        </button>
                                    @empty
                                        <p class="px-4 py-3 text-sm text-gray-400">Pasien tidak ditemukan</p>
                                    @endforelse
                                </div>
                            @endif
                        </div>
                        @if ($pasien_id)
                            <p class="mt-1.5 text-xs text-emerald-600 font-medium">
                                <i class="bi bi-check-circle-fill mr-1"></i>
                                {{ $this->getSelectedPasienName() }}
                                <button type="button" wire:click="clearPasienSearch"
                                    class="ml-1 text-gray-400 hover:text-red-500">
                                    <i class="bi bi-x"></i>
                                </button>
                            </p>
                        @endif
                        @error('pasien_id')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Obat -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Obat yang Diresepkan
                        </label>

                        <!-- Add Obat Row -->
                        <div class="flex items-center gap-2" @click.away.stop="blurObatSearch">
                            <div class="relative flex-1">
                                <input type="text" wire:model.live.debounce="obat_search"
                                    wire:focus="focusObatSearch" wire:blur="blurObatSearch"
                                    wire:keydown.enter.prevent="addDrugFromSearch"
                                    placeholder="Ketik nama obat untuk menambahkan..."
                                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" />

                                {{-- Dropdown Obat --}}
                                @if ($obat_focused && $filtered_obats->isNotEmpty())
                                    <div
                                        class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-elevation-lg max-h-56 overflow-y-auto">
                                        @foreach ($filtered_obats as $d)
                                            <button type="button" wire:click="addDrug({{ $d->id }})"
                                                class="w-full flex items-center gap-3 px-4 py-2.5 text-left hover:bg-indigo-50 transition-colors">

                                                <div
                                                    class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold text-xs shrink-0">
                                                    {{ strtoupper(substr($d->nama, 0, 2)) }}
                                                </div>

                                                <div>
                                                    <p class="text-sm font-semibold text-gray-900">
                                                        {{ $d->nama }} <span
                                                            class="text-gray-400 font-normal">({{ $d->kode }})</span>
                                                    </p>
                                                    <p class="text-xs text-gray-400">
                                                        {{ $d->type }} &middot;
                                                        Stok: {{ $d->stok }}
                                                    </p>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            
                        </div>

                        {{-- Selected Obat --}}
                        @if (count($selected_drugs) > 0)
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($selected_drugs as $index => $item)
                                    <div
                                        class="flex items-center gap-2 px-3 py-2 rounded-xl border border-indigo-100 bg-indigo-50 text-indigo-700 shadow-sm max-w-[180px]">

                                        {{-- Icon --}}
                                        <div
                                            class="w-7 h-7 rounded-lg bg-white flex items-center justify-center text-indigo-600 shrink-0">
                                            <i class="bi bi-capsule-pill text-xs"></i>
                                        </div>

                                        {{-- Obat Name --}}
                                        <span class="text-sm font-medium truncate">
                                            {{ $item['nama'] }}
                                        </span>

                                        {{-- Remove --}}
                                        <button type="button" wire:click="removeDrug({{ $index }})"
                                            class="ml-auto text-indigo-400 hover:text-red-500 transition-colors shrink-0">
                                            <i class="bi bi-x-lg text-xs"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @error('selected_drugs')
                            <p class="mt-1.5 text-xs text-red-500">
                                {{ $message }}
                            </p>
                        @enderror

                        @error('selected_drugs.*.obat_id')
                            <p class="mt-1.5 text-xs text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-100"></div>

                <!-- Row 3: Keluhan + Diagnosa -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="keluhan">
                            Keluhan Utama
                        </label>
                        <textarea id="keluhan" wire:model="keluhan" rows="3"
                            placeholder="Keluhan yang disampaikan pasien..."
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all resize-none"></textarea>
                        @error('keluhan')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="diagnosa">
                            Diagnosa <span class="text-red-500">*</span>
                        </label>
                        <textarea id="diagnosa" wire:model="diagnosa" rows="3" placeholder="Hasil diagnosa..."
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all resize-none"></textarea>
                        @error('diagnosa')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-100"></div>

                <!-- Row 4: Vital Signs -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Tanda Vital</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1" for="tekanan_darah">Tensi
                                    Darah</label>
                                <input type="text" id="tekanan_darah" wire:model="tekanan_darah"
                                    placeholder="120/80"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all" />
                                @error('tekanan_darah')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1"
                                    for="suhu_tubuh">Suhu</label>
                                <input type="text" id="suhu_tubuh" wire:model="suhu_tubuh"
                                    placeholder="36.5 &deg;C"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1" for="berat_badan">Berat
                                    Badan</label>
                                <input type="text" id="berat_badan" wire:model="berat_badan" placeholder="60 kg"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1" for="tinggi_badan">Tinggi
                                    Badan</label>
                                <input type="text" id="tinggi_badan" wire:model="tinggi_badan" placeholder="160 cm"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1" for="detak_jantung">Nadi
                                    (x/menit)</label>
                                <input type="number" id="detak_jantung" wire:model="detak_jantung" placeholder="72"
                                    min="0"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all" />
                                @error('detak_jantung')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1"
                                    for="laju_pernapasan">Pernapasan (x/menit)</label>
                                <input type="number" id="laju_pernapasan" wire:model="laju_pernapasan"
                                    placeholder="20" min="0"
                                    class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all" />
                                @error('laju_pernapasan')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-100"></div>

                <!-- Row 5: Catatan -->
                <div class="gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="catatan">
                            Catatan Tambahan
                        </label>
                        <textarea id="catatan" wire:model="catatan" rows="3"
                            placeholder="Catatan lain untuk pasien..."
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all resize-none"></textarea>
                        @error('catatan')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/50">
                <div class="flex items-center gap-2 text-xs text-gray-400">
                    <i class="bi bi-shield-check"></i>
                    <span>Data dicatat oleh <strong>{{ Auth::user()?->name ?? 'Admin' }}</strong></span>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="closeModal"
                        class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-colors">
                        Batal
                    </button>
                    <button type="button" wire:click="save"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold shadow-elevation-sm transition-all">
                        <i class="bi bi-check-lg"></i>
                        {{ $editMode ? 'Simpan Perubahan' : 'Tambah Rekam Medis' }}
                    </button>
                </div>
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
                <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center">
                    <i class="bi bi-exclamation-triangle-fill text-2xl text-red-500"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Hapus Rekam Medis?</h3>
                    <p class="text-sm text-gray-500 mt-1">Tindakan ini tidak dapat dibatalkan. Data rekam medis akan
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