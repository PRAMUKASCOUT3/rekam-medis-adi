<?php

use Livewire\Component;
use App\Models\Pasien;
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
    public string $sortColumn = 'created_at';
    public string $sortDirection = 'desc';

    // Calculated
    public int $total_pasien = 0;

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
        $query = Pasien::query();

        // Apply search filter
        if ($this->search) {
            $query
                ->where('nama', 'like', '%' . $this->search . '%')
                ->orWhere('nik', 'like', '%' . $this->search . '%')
                ->orWhere('no_telpon', 'like', '%' . $this->search . '%');
        }

        // Apply date filter based on patient creation date (registered date)
        if ($this->tanggal_dari) {
            $query->whereDate('created_at', '>=', $this->tanggal_dari);
        }
        if ($this->tanggal_sampai) {
            $query->whereDate('created_at', '<=', $this->tanggal_sampai);
        }

        $pasiens = $query->orderBy($this->sortColumn, $this->sortDirection)->paginate($this->perPage);

        $this->total_pasien = Pasien::count();

        return view('pages.patient.laporan', [
            'pasiens' => $pasiens,
            'total_pasien' => $this->total_pasien,
            'perPage' => $this->perPage,
            'sortColumn' => 'created_at',
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

    /**
     * Generate and download PDF report
     */
    public function downloadPdf()
    {
        $query = Pasien::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nama', 'like', '%' . $this->search . '%')
                    ->orWhere('nik', 'like', '%' . $this->search . '%')
                    ->orWhere('no_telpon', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->tanggal_dari) {
            $query->whereDate('created_at', '>=', $this->tanggal_dari);
        }

        if ($this->tanggal_sampai) {
            $query->whereDate('created_at', '<=', $this->tanggal_sampai);
        }

        $pasiens = $query->orderBy('nama')->get();

        // FIX UTF-8 ERROR
        $pasiens = $pasiens->map(function ($item) {
            foreach ($item->getAttributes() as $key => $value) {
                if (is_string($value)) {
                    $item->$key = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
            }

            return $item;
        });

        $total = $pasiens->count();
        $now = Carbon::now()->format('d/m/Y H:i');

        $html = view('pdf.patients_report', [
            'pasiens' => $pasiens,
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

        $fileName = 'laporan-pasien-' . Carbon::now()->format('Y-m-d_Hi') . '.pdf';

        return response()->streamDownload(fn() => print $pdf->output(), $fileName);
    }

    /**
     * Get statistics for the page
     */
    public function getAllStatistics(): array
    {
        $dari = $this->tanggal_dari ? Carbon::parse($this->tanggal_dari) : Carbon::now()->startOfMonth();
        $sampai = $this->tanggal_sampai ? Carbon::parse($this->tanggal_sampai)->endOfDay() : Carbon::now()->endOfDay();

        return [
            'total_semua' => Pasien::count(),
            'total_dari_sampai' => Pasien::whereBetween('created_at', [$dari, $sampai])->count(),
            'jumlah_laki' => Pasien::whereBetween('created_at', [$dari, $sampai])
                ->where('jenis_kelamin', 'L')
                ->count(),
            'jumlah_perempuan' => Pasien::whereBetween('created_at', [$dari, $sampai])
                ->where('jenis_kelamin', 'P')
                ->count(),
            'dewasa' => Pasien::whereBetween('created_at', [$dari, $sampai])
                ->where('jenis_pasien', 'dewasa')
                ->count(),
            'anak_anak' => Pasien::whereBetween('created_at', [$dari, $sampai])
                ->where('jenis_pasien', 'anak-anak')
                ->count(),
            'ibu_hamil' => Pasien::whereBetween('created_at', [$dari, $sampai])
                ->where('jenis_pasien', 'ibu hamil')
                ->count(),
            'bayi' => Pasien::whereBetween('created_at', [$dari, $sampai])
                ->where('jenis_pasien', 'bayi')
                ->count(),
        ];
    }

    protected function view($data = [])
    {
        return app('view')->file('D:\laragon\www\rekam-medis\storage\framework/views/livewire/views/21f6e749.blade.php', $data);
    }
};

