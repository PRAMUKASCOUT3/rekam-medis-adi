<?php

use Livewire\Component;
use App\Models\Obat;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

return new class extends Component {
    use WithPagination;

    // Filter
    public ?string $tanggal_dari = null;
    public ?string $tanggal_sampai = null;
    public ?int $stok_min = null;
    public ?int $stok_max = null;
    public string $search = '';
    public string $type_filter = '';
    public int $perPage = 10;

    // Sorting
    public string $sortColumn = 'created_at';
    public string $sortDirection = 'desc';

    // Stats
    public int $total_obat = 0;
    public int $total_stok = 0;
    public int $stok_habis = 0;
    public int $stok_menipis = 0;
    public int $stok_aman = 0;

    protected function rules(): array
    {
        return [];
    }

    public function mount(): void
    {
        $this->tanggal_dari = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_sampai = Carbon::now()->format('Y-m-d');
    }

    public function render()
    {
        $query = Obat::query();

        if ($this->search) {
            $query->where('nama', 'like', '%' . $this->search . '%')->orWhere('kode', 'like', '%' . $this->search . '%');
        }

        if ($this->type_filter) {
            $query->where('type', $this->type_filter);
        }

        if ($this->stok_min !== null && $this->stok_min !== '') {
            $query->where('stok', '>=', (int) $this->stok_min);
        }
        if ($this->stok_max !== null && $this->stok_max !== '') {
            $query->where('stok', '<=', (int) $this->stok_max);
        }

        if ($this->tanggal_dari) {
            $query->whereDate('created_at', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $query->whereDate('created_at', '<=', $this->tanggal_sampai);
        }

        $obats = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        $this->recalculateStats();

        return view('pages.drugs.laporan', [
            'obats' => $obats,
            'total_obat' => $this->total_obat,
            'total_stok' => $this->total_stok,
            'stok_habis' => $this->stok_habis,
            'stok_menipis' => $this->stok_menipis,
            'stok_aman' => $this->stok_aman,
            'perPage' => $this->perPage,
            'type_filter' => $this->type_filter,
            'sortColumn' => $this->sortColumn,
            'sortDirection' => $this->sortDirection,
        ]);
    }

    private function recalculateStats(): void
    {
        $q = Obat::query();
        if ($this->tanggal_dari) {
            $q->whereDate('created_at', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $q->whereDate('created_at', '<=', $this->tanggal_sampai);
        }
        $all = $q->get(['stok']);
        $this->total_obat = $all->count();
        $this->total_stok = $all->sum('stok');
        $this->stok_habis = $all->where('stok', 0)->count();
        $this->stok_menipis = $all->where('stok', '>', 0)->where('stok', '<', 10)->count();
        $this->stok_aman = $all->where('stok', '>=', 10)->count();
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }
    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }
    public function updatedTanggalDari(): void
    {
        $this->resetPage();
    }
    public function updatedTanggalSampai(): void
    {
        $this->resetPage();
    }
    public function updatedStokMin(): void
    {
        $this->resetPage();
    }
    public function updatedStokMax(): void
    {
        $this->resetPage();
    }
    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'type_filter', 'stok_min', 'stok_max']);
        $this->tanggal_dari = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_sampai = Carbon::now()->format('Y-m-d');
    }

    public function downloadPdf()
    {
        $query = Obat::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nama', 'like', '%' . $this->search . '%')->orWhere('kode', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->type_filter) {
            $query->where('type', $this->type_filter);
        }

        if ($this->stok_min !== null && $this->stok_min !== '') {
            $query->where('stok', '>=', (int) $this->stok_min);
        }

        if ($this->stok_max !== null && $this->stok_max !== '') {
            $query->where('stok', '<=', (int) $this->stok_max);
        }

        if ($this->tanggal_dari) {
            $query->whereDate('created_at', '>=', $this->tanggal_dari);
        }

        if ($this->tanggal_sampai) {
            $query->whereDate('created_at', '<=', $this->tanggal_sampai);
        }

        $obats = $query->orderBy('nama')->get();

        // Fix UTF-8
        $obatData = $obats
            ->map(function ($o) {
                return [
                    'nama' => mb_convert_encoding((string) $o->nama, 'UTF-8', 'UTF-8'),
                    'kode' => mb_convert_encoding((string) $o->kode, 'UTF-8', 'UTF-8'),
                    'type' => mb_convert_encoding((string) $o->type, 'UTF-8', 'UTF-8'),
                    'satuan' => mb_convert_encoding((string) $o->satuan, 'UTF-8', 'UTF-8'),
                    'stok' => (int) $o->stok,
                    'created_at' => optional($o->created_at)->format('d/m/Y H:i') ?? '-',
                ];
            })
            ->all();

        $total = count($obatData);
        $now = Carbon::now()->format('d/m/Y H:i');

        $html = view('pdf.drugs_report', [
            'obats' => $obatData,
            'total' => $total,
            'total_stok' => collect($obatData)->sum('stok'),
            'jenis' => $this->type_filter ? strtoupper($this->type_filter) : null,
            'stok_min' => $this->stok_min,
            'stok_max' => $this->stok_max,
            'tanggal_dari' => $this->tanggal_dari,
            'tanggal_sampai' => $this->tanggal_sampai,
            'printed_at' => $now,
            'stok_habis' => collect($obatData)->where('stok', 0)->count(),
            'stok_menipis' => collect($obatData)->where('stok', '>', 0)->where('stok', '<', 10)->count(),
            'stok_aman' => collect($obatData)->where('stok', '>=', 10)->count(),
        ])->render();

        // Fix malformed UTF-8
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('A4', 'landscape')
            ->setOptions([
                'isRemoteEnabled' => true,
                'defaultFont' => 'dejavusans',
                'isHtml5ParserEnabled' => true,
            ]);

        $fileName = 'laporan-obat-' . Carbon::now()->format('Y-m-d_Hi') . '.pdf';

        return response()->streamDownload(fn() => print $pdf->output(), $fileName);
    }

    public function getAllStatistics(): array
    {
        $q = Obat::query();
        if ($this->tanggal_dari) {
            $q->whereDate('created_at', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $q->whereDate('created_at', '<=', $this->tanggal_sampai);
        }
        $all = $q->get(['stok', 'type']);
        return [
            'total' => $all->count(),
            'total_stok' => $all->sum('stok'),
            'stok_habis' => $all->where('stok', 0)->count(),
            'stok_menipis' => $all->where('stok', '>', 0)->where('stok', '<', 10)->count(),
            'stok_aman' => $all->where('stok', '>=', 10)->count(),
        ];
    }

    protected function view($data = [])
    {
        return app('view')->file('D:\laragon\www\rekam-medis\storage\framework/views/livewire/views/157dfbfe.blade.php', $data);
    }
};
