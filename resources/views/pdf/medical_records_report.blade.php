<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <style>
        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
            color: #1f2937;
        }

        * {
            box-sizing: border-box;
        }

        /* ================= HEADER ================= */

        .header-table {
            width: 100%;
            border-bottom: 3px solid #e11d48;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .logo-box {
            width: 50px;
            text-align: center;
            vertical-align: middle;
        }

        .logo-icon {
            width: 42px;
            height: 42px;
            background: #e11d48;
            border-radius: 6px;
            color: white;
            font-size: 22px;
            font-weight: bold;
            line-height: 42px;
            text-align: center;
        }

        .clinic-name {
            font-size: 16pt;
            font-weight: bold;
            color: #be123c;
        }

        .clinic-subtitle {
            font-size: 8pt;
            color: #6b7280;
        }

        .report-title {
            text-align: right;
        }

        .report-title h2 {
            margin: 0;
            font-size: 14pt;
            color: #111827;
        }

        .report-meta {
            font-size: 8pt;
            color: #6b7280;
            margin-top: 4px;
        }

        /* ================= STATISTICS ================= */

        .stats-table {
            width: 100%;
            margin-bottom: 12px;
            border-spacing: 5px;
        }

        .stat-card {
            border: 1px solid #ffe4e6;
            background: #fff1f2;
            border-radius: 6px;
            text-align: center;
            padding: 10px 5px;
        }

        .stat-number {
            font-size: 16pt;
            font-weight: bold;
            color: #be123c;
        }

        .stat-label {
            font-size: 8pt;
            color: #4b5563;
        }

        /* ================= TABLE ================= */

        table.data {
            width: 100%;
            border-collapse: collapse;
        }

        table.data thead th {
            background: #be123c;
            color: white;
            font-size: 8pt;
            padding: 8px;
            text-align: left;
        }

        table.data tbody td {
            border-bottom: 1px solid #e5e7eb;
            padding: 7px;
            vertical-align: top;
            font-size: 8pt;
        }

        table.data tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .name {
            font-weight: bold;
            color: #111827;
        }

        .nik {
            font-size: 7pt;
            color: #6b7280;
        }

        .rm-number {
            font-size: 7.5pt;
            font-weight: bold;
            color: #be123c;
            font-family: monospace;
        }

        .diagnosa {
            font-size: 8pt;
            color: #374151;
        }

        .badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 4px;
            font-size: 7pt;
            font-weight: bold;
        }

        .male {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .female {
            background: #fce7f3;
            color: #be185d;
        }

        .small-text {
            color: #6b7280;
            font-size: 7pt;
        }

        /* ================= VITAL SIGNS ================= */

        .vital-label {
            font-size: 7pt;
            color: #6b7280;
        }

        .vital-value {
            font-size: 8pt;
            font-weight: 600;
            color: #374151;
        }

        /* ================= FOOTER ================= */

        .footer {
            margin-top: 12px;
            border-top: 1px solid #d1d5db;
            padding-top: 6px;
            font-size: 8pt;
            color: #6b7280;
            text-align: right;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td width="60">
                <div class="logo-icon">+</div>
            </td>

            <td>
                <div class="clinic-name">
                    SISTEM INFORMASI KESEHATAN BIDAN
                </div>
                <div class="clinic-subtitle">
                    Rekam Medis Management System
                </div>
            </td>

            <td align="right">
                <div class="report-title">
                    <h2>LAPORAN REKAM MEDIS</h2>

                    <div class="report-meta">
                        @if ($tanggal_dari && $tanggal_sampai)
                            Periode:
                            <strong>{{ $tanggal_dari }}</strong>
                            s/d
                            <strong>{{ $tanggal_sampai }}</strong>
                            <br>
                        @endif

                        Dicetak:
                        {{ $printed_at }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    @php
        use App\Models\RekamMedis;
        use Illuminate\Support\Carbon;

        $dari = $tanggal_dari ? Carbon::parse($tanggal_dari) : Carbon::now()->startOfMonth();
        $sampai = $tanggal_sampai ? Carbon::parse($tanggal_sampai)->endOfDay() : Carbon::now()->endOfDay();

        $base = RekamMedis::whereBetween('tanggal_pemeriksaan', [$dari, $sampai]);

        $stats = [
            'total_semua' => RekamMedis::count(),
            'total_periode' => $base->count(),
            'laki' => $base->whereHas('pasien', fn($q) => $q->where('jenis_kelamin', 'L'))->count(),
            'perempuan' => $base->whereHas('pasien', fn($q) => $q->where('jenis_kelamin', 'P'))->count(),
            'dengan_obat' => RekamMedis::whereHas('obats')->whereBetween('tanggal_pemeriksaan', [$dari, $sampai])->count(),
            'tanpa_obat' => RekamMedis::whereDoesntHave('obats')->whereBetween('tanggal_pemeriksaan', [$dari, $sampai])->count(),
            'dengan_diagnosa' => $base->whereNotNull('diagnosa')->count(),
            'hari_ini' => RekamMedis::whereDate('tanggal_pemeriksaan', Carbon::today())->count(),
        ];

        $statCells = [
            [$stats['total_semua'], 'Total RM'],
            [$stats['total_periode'], 'Periode Ini'],
            [$stats['laki'], 'Laki-laki'],
            [$stats['perempuan'], 'Perempuan'],
            [$stats['dengan_obat'], 'Dengan Obat'],
            [$stats['tanpa_obat'], 'Tanpa Obat'],
            [$stats['dengan_diagnosa'], 'Ada Diagnosa'],
            [$stats['hari_ini'], 'Hari Ini'],
        ];
    @endphp

    <!-- STATISTICS -->
    <table class="stats-table">
        <tr>
            @foreach ($statCells as [$val, $label])
                <td width="12.5%">
                    <div class="stat-card">
                        <div class="stat-number">
                            {{ number_format($val) }}
                        </div>

                        <div class="stat-label">
                            {{ $label }}
                        </div>
                    </div>
                </td>
            @endforeach
        </tr>
    </table>

    <!-- DATA TABLE -->
    <table class="data">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="8%">No. RM</th>
                <th width="18%">Nama Pasien</th>
                <th width="6%">Kelamin</th>
                <th width="10%">Tensi / Suhu</th>
                <th width="14%">Diagnosa</th>
                <th width="10%">Petugas</th>
                <th width="12%">Tgl Pemeriksaan</th>
                <th width="18%">Catatan / Keluhan</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($records as $i => $r)
                <tr>
                    <td>{{ $i + 1 }}</td>

                    <td>
                        <div class="rm-number">
                            {{ $r->nomor_rekam_medis }}
                        </div>
                    </td>

                    <td>
                        <div class="name">
                            {{ $r->pasien?->nama ?? 'N/A' }}
                        </div>
                        <div class="nik">
                            {{ $r->pasien?->nik ?? '-' }}
                        </div>
                    </td>

                    <td align="center">
                        @if ($r->pasien?->jenis_kelamin == 'L')
                            <span class="badge male">L</span>
                        @elseif($r->pasien?->jenis_kelamin == 'P')
                            <span class="badge female">P</span>
                        @else
                            <span class="badge">-</span>
                        @endif
                    </td>

                    <td>
                        @php
                            $vitalParts = collect([$r->tekanan_darah, $r->suhu_tubuh, $r->berat_badan ? $r->berat_badan . ' kg' : null, $r->tinggi_badan ? $r->tinggi_badan . ' cm' : null])->filter();
                            echo $vitalParts->count() > 0 ? $vitalParts->implode(' / ') : '-';
                        @endphp
                        @if ($r->detak_jantung || $r->laju_pernapasan)
                            <br>
                            <span class="small-text">
                                @if ($r->detak_jantung) Nadi {{ $r->detak_jantung }}x/menit @endif
                                @if ($r->laju_pernapasan)
                                    @if ($r->detak_jantung) &middot; @endif
                                    RR {{ $r->laju_pernapasan }}x/menit
                                @endif
                            </span>
                        @endif
                    </td>

                    <td>
                        <div class="diagnosa">
                            @if ($r->diagnosa)
                                {{ Str::limit($r->diagnosa, 60) }}
                            @else
                                -
                            @endif
                        </div>
                    </td>

                    <td>
                        {{ $r->user?->name ?? 'N/A' }}
                    </td>

                    <td>
                        @php
                            $tgl = Carbon::parse($r->tanggal_pemeriksaan);
                        @endphp
                        {{ $tgl->format('d/m/Y') }}
                        <br>
                        <span class="small-text">
                            {{ $tgl->format('H:i') }} WIB
                        </span>
                    </td>

                    <td>
                        @if ($r->keluhan)
                            <span class="small-text">
                                <i class="bi bi-chat-left-quote text-gray-400 mr-1"></i>{{ Str::limit($r->keluhan, 50) }}
                            </span>
                        @endif
                        @if ($r->catatan)
                            <br>
                            <span class="small-text">
                                <i class="bi bi-pencil-square text-gray-400 mr-1"></i>{{ Str::limit($r->catatan, 50) }}
                            </span>
                        @endif
                        @if (!$r->keluhan && !$r->catatan)
                            <span class="small-text">-</span>
                        @endif
                    </td>
                </tr>

            @empty
                <tr>
                    <td colspan="9" align="center">
                        Tidak ada data rekam medis
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        Total:
        <strong>{{ number_format($total) }}</strong>
        rekam medis |
        Dicetak:
        {{ $printed_at }}
    </div>

</body>

</html>
