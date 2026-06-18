<?php

use Livewire\Component;
use App\Models\Imunisasi;
use App\Models\Pasien;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\WithPagination;

return new class extends Component {
    use WithPagination;

    // Filter
    public ?string $tanggal_dari = null;
    public ?string $tanggal_sampai = null;
    public string $search = '';
    public int $perPage = 10;

    // Sorting
    public string $sortColumn = 'tanggal_imunisasi';
    public string $sortDirection = 'desc';

    // Calculated
    public int $total_imunisasi = 0;

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
        $query = Imunisasi::query()->with(['pasien:id,nama,jenis_kelamin,tanggal_lahir', 'user:id,name']);

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('pasien', function ($sq) {
                    $sq->where('nama', 'like', '%' . $this->search . '%');
                })
                ->orWhere('jenis_imunisasi', 'like', '%' . $this->search . '%')
                ->orWhere('nama_orang_tua', 'like', '%' . $this->search . '%')
                ->orWhereHas('user', function ($sq) {
                    $sq->where('name', 'like', '%' . $this->search . '%');
                });
            });
        }

        // Apply date filter on immunization date
        if ($this->tanggal_dari) {
            $query->whereDate('tanggal_imunisasi', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $query->whereDate('tanggal_imunisasi', '<=', $this->tanggal_sampai);
        }

        $records = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        $this->total_imunisasi = Imunisasi::count();

        return view('pages.immunization.laporan', [
            'records' => $records,
            'total_imunisasi' => $this->total_imunisasi,
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
     * Calculate patient age from given DOB string
     */
    public function calcAge(?string $dobValue): ?string
    {
        if (! $dobValue) {
            return null;
        }
        try {
            return Carbon::parse($dobValue)->age . ' tahun';
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate and download PDF report
     */
    public function downloadPdf()
    {
        $query = Imunisasi::query()->with(['pasien:id,nama,jenis_kelamin,tanggal_lahir', 'user:id,name']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('pasien', function ($sq) {
                    $sq->where('nama', 'like', '%' . $this->search . '%');
                })
                ->orWhere('jenis_imunisasi', 'like', '%' . $this->search . '%')
                ->orWhere('nama_orang_tua', 'like', '%' . $this->search . '%')
                ->orWhereHas('user', function ($sq) {
                    $sq->where('name', 'like', '%' . $this->search . '%');
                });
            });
        }

        if ($this->tanggal_dari) {
            $query->whereDate('tanggal_imunisasi', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $query->whereDate('tanggal_imunisasi', '<=', $this->tanggal_sampai);
        }

        $records = $query->orderBy('tanggal_imunisasi', 'desc')->get();

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

        $html = view('pdf.immunization_report', [
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

        $fileName = 'laporan-imunisasi-' . Carbon::now()->format('Y-m-d_Hi') . '.pdf';

        return response()->streamDownload(fn() => print $pdf->output(), $fileName);
    }

    /**
     * Get statistics for the page
     */
    public function getAllStatistics(): array
    {
        $dari = $this->tanggal_dari ? Carbon::parse($this->tanggal_dari) : Carbon::now()->startOfMonth();
        $sampai = $this->tanggal_sampai ? Carbon::parse($this->tanggal_sampai)->endOfDay() : Carbon::now()->endOfDay();

        $base = Imunisasi::whereBetween('tanggal_imunisasi', [$dari, $sampai]);

        return [
            'total_semua' => Imunisasi::count(),
            'total_periode' => $base->count(),
            'hari_ini' => Imunisasi::whereDate('tanggal_imunisasi', Carbon::today())->count(),
            'bulan_ini' => Imunisasi::whereMonth('tanggal_imunisasi', now()->month)->count(),
            'dengan_keterangan' => $base->whereNotNull('keterangan')->where('keterangan', '!=', '')->count(),
            'dengan_pengobatan' => $base->whereNotNull('pengobatan')->where('pengobatan', '!=', '')->count(),
            'dengan_alamat' => $base->whereNotNull('alamat')->where('alamat', '!=', '')->count(),
            'total_jenis' => Imunisasi::select('jenis_imunisasi')->distinct()->count(),
        ];
    }

    protected function view($data = [])
    {
        return app('view')->file('D:\laragon\www\rekam-medis\storage\framework/views/livewire/views/877d96f3.blade.php', $data);
    }
};

