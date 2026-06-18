<?php

use Livewire\Component;
use App\Models\Pasien;
use App\Models\RekamMedis;
use App\Models\Pregnancy;
use App\Models\Delivery;
use App\Models\Imunisasi;
use App\Models\Obat;
use Illuminate\Support\Carbon;
use Livewire\WithPagination;

return new class extends Component {
    use WithPagination;

    // ---------- Stat Cards ----------
    public int $total_pasien = 0;
    public int $kunjungan_hari_ini = 0;
    public int $kehamilan_aktif = 0;
    public int $imunisasi_hari_ini = 0;
    public int $persalinan_hari_ini = 0;
    public int $total_obat = 0;
    public int $total_user = 0;

    // ---------- Recent RM table ----------
    public int $perPage = 5;
    public string $sortColumn = 'tanggal_pemeriksaan';
    public string $sortDirection = 'desc';

    // ---------- Visit chart ----------
    public array $chartLabels = [];
    public array $chartValues = [];

    public function mount(): void
    {
        $this->loadStats();
        $this->loadVisitChart();
    }

    public function loadStats(): void
    {
        $today = Carbon::today();
        $this->total_pasien        = Pasien::count();
        $this->kunjungan_hari_ini  = RekamMedis::whereDate('tanggal_pemeriksaan', $today)->count();
        $this->kehamilan_aktif     = Pregnancy::count();
        $this->imunisasi_hari_ini  = Imunisasi::whereDate('tanggal_imunisasi', $today)->count();
        $this->persalinan_hari_ini = Delivery::whereDate('tanggal', $today)->count();
        $this->total_obat          = Obat::count();
        $this->total_user          = \App\Models\User::count();
    }

    public function loadVisitChart(): void
    {
        $days   = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $d    = Carbon::today()->subDays($i);
            $days[]   = $d->translatedFormat('D');
            $values[] = RekamMedis::whereDate('tanggal_pemeriksaan', $d)->count();
        }
        $this->chartLabels = $days;
        $this->chartValues = $values;
    }

    public function render()
    {
        $recentRecords = RekamMedis::query()
            ->with(['pasien:id,nama,jenis_kelamin', 'user:id,name'])
            ->orderBy($this->sortColumn, $this->sortDirection)
            ->paginate($this->perPage);

        $recentPregnancies = Pregnancy::query()
            ->with('user:id,name')
            ->orderBy('tanggal', 'desc')
            ->limit(3)
            ->get();

        $recentDeliveries = Delivery::query()
            ->with('user:id,name')
            ->orderBy('tanggal', 'desc')
            ->limit(3)
            ->get();

        $recentImmunizations = Imunisasi::query()
            ->with(['pasien:id,nama', 'user:id,name'])
            ->orderBy('tanggal_imunisasi', 'desc')
            ->limit(3)
            ->get();

        return view('pages.admin.dashboard', [
            'recentRecords'      => $recentRecords,
            'recentPregnancies'  => $recentPregnancies,
            'recentDeliveries'   => $recentDeliveries,
            'recentImmunizations'=> $recentImmunizations,
            'chartLabels'        => $this->chartLabels,
            'chartValues'        => $this->chartValues,
        ]);
    }

    protected function view($data = [])
    {
        return app('view')->file('D:\laragon\www\rekam-medis\storage\framework/views/livewire/views/2203f0af.blade.php', $data);
    }
};

