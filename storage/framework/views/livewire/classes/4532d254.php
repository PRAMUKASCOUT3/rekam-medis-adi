<?php

use Livewire\Component;
use App\Models\Pregnancy;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\WithPagination;

return new class extends Component {
    use WithPagination;

    // Filter
    public ?string $tanggal_dari = null;
    public ?string $tanggal_sampai = null;
    public string $search = '';
    public int $perPage = 10;

    // Sorting
    public string $sortColumn = 'tanggal';
    public string $sortDirection = 'desc';

    // Calculated
    public int $total_kehamilan = 0;

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
        $query = Pregnancy::query()->with('user:id,name');

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nama_istri', 'like', '%' . $this->search . '%')
                    ->orWhere('nama_suami', 'like', '%' . $this->search . '%')
                    ->orWhere('alamat', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($sq) {
                        $sq->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        // Apply date filter on tanggal pemeriksaan
        if ($this->tanggal_dari) {
            $query->whereDate('tanggal', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $query->whereDate('tanggal', '<=', $this->tanggal_sampai);
        }

        $records = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        $this->total_kehamilan = Pregnancy::count();

        return view('pages.pregnancy.laporan', [
            'records' => $records,
            'total_kehamilan' => $this->total_kehamilan,
            'perPage' => $this->perPage,
            'sortColumn' => $this->sortColumn,
            'sortDirection' => $this->sortDirection,
        ]);
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

    public function updatedTanggalDari(): void
    {
        $this->resetPage();
    }

    public function updatedTanggalSampai(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * Gestational age in weeks from HPHT
     */
    public function calcWeeks(?string $hphtValue): ?int
    {
        if (! $hphtValue) {
            return null;
        }
        try {
            return Carbon::parse($hphtValue)->diffInWeeks(now());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate and download PDF report
     */
    public function downloadPdf()
    {
        $query = Pregnancy::query()->with('user:id,name');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nama_istri', 'like', '%' . $this->search . '%')
                    ->orWhere('nama_suami', 'like', '%' . $this->search . '%')
                    ->orWhere('alamat', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($sq) {
                        $sq->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        if ($this->tanggal_dari) {
            $query->whereDate('tanggal', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $query->whereDate('tanggal', '<=', $this->tanggal_sampai);
        }

        $records = $query->orderBy('tanggal', 'desc')->get();

        // FIX UTF-8 ERROR
        $records = $records->map(function ($item) {
            foreach ($item->getAttributes() as $key => $value) {
                if (is_string($value)) {
                    $item->$key = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
            }
            if ($item->user) {
                foreach ($item->user->getAttributes() as $key => $value) {
                    if (is_string($value)) {
                        $item->user->$key = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }
                }
            }
            return $item;
        });

        $total = $records->count();
        $now = Carbon::now()->format('d/m/Y H:i');

        $html = view('pdf.pregnancy_report', [
            'records' => $records,
            'total' => $total,
            'tanggal_dari' => $this->tanggal_dari,
            'tanggal_sampai' => $this->tanggal_sampai,
            'search' => $this->search,
            'printed_at' => $now,
        ])->render();

        // Normalize HTML UTF-8
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('A4', 'landscape')
            ->setOptions([
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
            ]);

        $fileName = 'laporan-kehamilan-' . Carbon::now()->format('Y-m-d_Hi') . '.pdf';

        return response()->streamDownload(fn() => print $pdf->output(), $fileName);
    }

    /**
     * Get statistics for the page
     */
    public function getAllStatistics(): array
    {
        $dari = $this->tanggal_dari ? Carbon::parse($this->tanggal_dari) : Carbon::now()->startOfMonth();
        $sampai = $this->tanggal_sampai ? Carbon::parse($this->tanggal_sampai)->endOfDay() : Carbon::now()->endOfDay();

        $base = Pregnancy::whereBetween('tanggal', [$dari, $sampai]);

        return [
            'total_semua' => Pregnancy::count(),
            'total_periode' => $base->count(),
            'hari_ini' => Pregnancy::whereDate('tanggal', Carbon::today())->count(),
            'bulan_ini' => Pregnancy::whereMonth('tanggal', now()->month)->count(),
            'trim_1' => $base->whereNull('hpht')->orWhere(function ($q) use ($sampai) {
                $q->whereNotNull('hpht')
                    ->where(function ($sq) use ($sampai) {
                        // G1-P0-A0~ (rough trimester 1)
                        for ($w = 0; $w <= 12; $w++) {
                            $date = (clone $sampai)->subWeeks($w)->format('Y-m-d');
                            $sq->orWhereDate('hpht', $date);
                        }
                    });
            })->count(),
            'trim_3' => $base->whereNotNull('hpht')->where(function ($q) use ($sampai) {
                // >= 28 weeks meaning HPHT >= today - 27 weeks
                $cutoff = (clone $sampai)->subWeeks(27)->format('Y-m-d');
                $q->whereDate('hpht', '>=', $cutoff);
            })->count(),
            'dengan_alamat' => $base->whereNotNull('alamat')->where('alamat', '!=', '')->count(),
        ];
    }

    protected function view($data = [])
    {
        return app('view')->file('D:\laragon\www\rekam-medis\storage\framework/views/livewire/views/4532d254.blade.php', $data);
    }
};

