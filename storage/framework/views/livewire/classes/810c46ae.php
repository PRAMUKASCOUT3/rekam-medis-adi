<?php

use Livewire\Component;
use App\Models\RekamMedis;
use App\Models\Pasien;
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
    public string $sortColumn = 'tanggal_pemeriksaan';
    public string $sortDirection = 'desc';

    // Calculated
    public int $total_rm = 0;

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
        $query = RekamMedis::query()->with(['pasien:id,nama,jenis_kelamin', 'user:id,name']);

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nomor_rekam_medis', 'like', '%' . $this->search . '%')
                    ->orWhereHas('pasien', function ($sq) {
                        $sq->where('nama', 'like', '%' . $this->search . '%');
                    })
                    ->orWhere('diagnosa', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($sq) {
                        $sq->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        // Apply date filter on examination date
        if ($this->tanggal_dari) {
            $query->whereDate('tanggal_pemeriksaan', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $query->whereDate('tanggal_pemeriksaan', '<=', $this->tanggal_sampai);
        }

        $records = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        $this->total_rm = RekamMedis::count();

        return view('pages.medical_records.laporan', [
            'records' => $records,
            'total_rm' => $this->total_rm,
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
     * Generate and download PDF report
     */
    public function downloadPdf()
    {
        $query = RekamMedis::query()->with(['pasien:id,nama,jenis_kelamin', 'user:id,name']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nomor_rekam_medis', 'like', '%' . $this->search . '%')
                    ->orWhereHas('pasien', function ($sq) {
                        $sq->where('nama', 'like', '%' . $this->search . '%');
                    })
                    ->orWhere('diagnosa', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($sq) {
                        $sq->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        if ($this->tanggal_dari) {
            $query->whereDate('tanggal_pemeriksaan', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $query->whereDate('tanggal_pemeriksaan', '<=', $this->tanggal_sampai);
        }

        $records = $query->orderBy('tanggal_pemeriksaan', 'desc')->get();

        // FIX UTF-8 ERROR
        $records = $records->map(function ($item) {
            foreach ($item->getAttributes() as $key => $value) {
                if (is_string($value)) {
                    $item->$key = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
            }
            if ($item->pasien) {
                foreach ($item->pasien->getAttributes() as $key => $value) {
                    if (is_string($value)) {
                        $item->pasien->$key = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }
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

        $html = view('pdf.medical_records_report', [
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

        $fileName = 'laporan-rekam-medis-' . Carbon::now()->format('Y-m-d_Hi') . '.pdf';

        return response()->streamDownload(fn() => print $pdf->output(), $fileName);
    }

    /**
     * Get statistics for the page
     */
    public function getAllStatistics(): array
    {
        $dari = $this->tanggal_dari ? Carbon::parse($this->tanggal_dari) : Carbon::now()->startOfMonth();
        $sampai = $this->tanggal_sampai ? Carbon::parse($this->tanggal_sampai)->endOfDay() : Carbon::now()->endOfDay();

        $base = RekamMedis::whereBetween('tanggal_pemeriksaan', [$dari, $sampai]);

        return [
            'total_semua' => RekamMedis::count(),
            'total_periode' => $base->count(),
            'laki' => $base->whereHas('pasien', fn($q) => $q->where('jenis_kelamin', 'L'))->count(),
            'perempuan' => $base->whereHas('pasien', fn($q) => $q->where('jenis_kelamin', 'P'))->count(),
            'dengan_obat' => RekamMedis::whereHas('obats')->whereBetween('tanggal_pemeriksaan', [$dari, $sampai])->count(),
            'tanpa_obat' => RekamMedis::whereDoesntHave('obats')->whereBetween('tanggal_pemeriksaan', [$dari, $sampai])->count(),
            'dengan_diagnosa' => $base->whereNotNull('diagnosa')->count(),
            'hari_ini' => RekamMedis::whereDate('tanggal_pemeriksaan', Carbon::today())->count(),
        ];
    }

    protected function view($data = [])
    {
        return app('view')->file('D:\laragon\www\rekam-medis\storage\framework/views/livewire/views/810c46ae.blade.php', $data);
    }
};

