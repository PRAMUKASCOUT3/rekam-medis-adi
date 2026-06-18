<?php

use Livewire\Component;
use App\Models\Kb;
use Illuminate\Support\Carbon;
use Livewire\WithPagination;

return new class extends Component {
    use WithPagination;

    public ?string $tanggal_dari = null;
    public ?string $tanggal_sampai = null;
    public string $search = '';
    public int $perPage = 10;

    public string $sortColumn = 'tanggal';
    public string $sortDirection = 'desc';

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
        $query = Kb::query()->with('user');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nama_istri', 'like', '%' . $this->search . '%')
                    ->orWhere('nama_suami', 'like', '%' . $this->search . '%')
                    ->orWhere('no_regis', 'like', '%' . $this->search . '%')
                    ->orWhere('metode_kb', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->tanggal_dari) {
            $query->whereDate('tanggal', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $query->whereDate('tanggal', '<=', $this->tanggal_sampai);
        }

        $records = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        return view('pages.kb.laporan', [
            'records' => $records,
            'perPage' => $this->perPage,
            'sortColumn' => 'tanggal',
            'sortDirection' => 'desc',
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

    public function downloadPdf()
    {
        $query = Kb::query()->with('user');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nama_istri', 'like', '%' . $this->search . '%')
                    ->orWhere('nama_suami', 'like', '%' . $this->search . '%')
                    ->orWhere('no_regis', 'like', '%' . $this->search . '%')
                    ->orWhere('metode_kb', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->tanggal_dari) {
            $query->whereDate('tanggal', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $query->whereDate('tanggal', '<=', $this->tanggal_sampai);
        }

        $records = $query->orderBy('tanggal')->get();

        $records = $records->map(function ($item) {
            foreach ($item->getAttributes() as $key => $value) {
                if (is_string($value)) {
                    $item->$key = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
            }
            return $item;
        });

        $total = $records->count();
        $now = Carbon::now()->format('d/m/Y H:i');

        $html = view('pdf.kb_report', [
            'records' => $records,
            'total' => $total,
            'tanggal_dari' => $this->tanggal_dari,
            'tanggal_sampai' => $this->tanggal_sampai,
            'search' => $this->search,
            'printed_at' => $now,
        ])->render();

        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('A4', 'landscape')
            ->setOptions([
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
            ]);

        $fileName = 'laporan-kb-' . Carbon::now()->format('Y-m-d_Hi') . '.pdf';

        return response()->streamDownload(fn() => print $pdf->output(), $fileName);
    }

    public function getAllStatistics(): array
    {
        $dari = $this->tanggal_dari ? Carbon::parse($this->tanggal_dari) : Carbon::now()->startOfMonth();
        $sampai = $this->tanggal_sampai ? Carbon::parse($this->tanggal_sampai)->endOfDay() : Carbon::now()->endOfDay();

        return [
            'total_semua' => Kb::count(),
            'total_dari_sampai' => Kb::whereBetween('tanggal', [$dari, $sampai])->count(),
            'total_pil' => Kb::whereBetween('tanggal', [$dari, $sampai])->where('metode_kb', 'Pil')->count(),
            'total_suntik' => Kb::whereBetween('tanggal', [$dari, $sampai])->where('metode_kb', 'Suntik')->count(),
            'total_iud' => Kb::whereBetween('tanggal', [$dari, $sampai])->where('metode_kb', 'IUD/IUCD')->count(),
            'total_implan' => Kb::whereBetween('tanggal', [$dari, $sampai])->where('metode_kb', 'Implant')->count(),
            'total_lainnya' => Kb::whereBetween('tanggal', [$dari, $sampai])
                ->whereNotIn('metode_kb', ['Pil', 'Suntik', 'IUD/IUCD', 'Implant'])
                ->count(),
            'kunjungan_hari_ini' => Kb::whereDate('tanggal_kunjungan', now()->toDateString())->count(),
        ];
    }

    protected function view($data = [])
    {
        return app('view')->file('D:\laragon\www\rekam-medis\storage\framework/views/livewire/views/72c88a10.blade.php', $data);
    }
};

