<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    @php use Illuminate\Support\Str; @endphp

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
            border-bottom: 3px solid #059669;
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
            background: #059669;
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
            color: #047857;
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
            border: 1px solid #d1fae5;
            background: #ecfdf5;
            border-radius: 6px;
            text-align: center;
            padding: 10px 5px;
        }

        .stat-number {
            font-size: 16pt;
            font-weight: bold;
            color: #047857;
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
            background: #047857;
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

        .small-text {
            color: #6b7280;
            font-size: 7pt;
        }

        .badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 4px;
            font-size: 7.5pt;
            font-weight: bold;
        }

        .success-badge {
            background: #ecfdf5;
            color: #065f46;
        }

        .info-badge {
            background: #eff6ff;
            color: #1d4ed8;
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
                    <h2>LAPORAN DATA KB</h2>

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
        use Illuminate\Support\Carbon;
        use App\Models\Kb;
        $dari = $tanggal_dari ? Carbon::parse($tanggal_dari) : Carbon::now()->startOfMonth();
        $sampai = $tanggal_sampai ? Carbon::parse($tanggal_sampai)->endOfDay() : Carbon::now()->endOfDay();
        $stats = [
            'total' => $total,
            'periode' => Kb::whereBetween('tanggal', [$dari, $sampai])->count(),
            'pil' => Kb::whereBetween('tanggal', [$dari, $sampai])->where('metode_kb', 'Pil')->count(),
            'suntik' => Kb::whereBetween('tanggal', [$dari, $sampai])->where('metode_kb', 'Suntik')->count(),
            'iud' => Kb::whereBetween('tanggal', [$dari, $sampai])->where('metode_kb', 'IUD/IUCD')->count(),
            'implan' => Kb::whereBetween('tanggal', [$dari, $sampai])->where('metode_kb', 'Implant')->count(),
            'lainnya' => Kb::whereBetween('tanggal', [$dari, $sampai])
                ->whereNotIn('metode_kb', ['Pil', 'Suntik', 'IUD/IUCD', 'Implant'])
                ->count(),
            'kunjungan_hari_ini' => Kb::whereDate('tanggal_kunjungan', now()->toDateString())->count(),
        ];
    @endphp

    <!-- STATISTICS -->
    <table class="stats-table">
        <tr>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($stats['total']) }}</div>
                    <div class="stat-label">Total Data</div>
                </div>
            </td>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($stats['periode']) }}</div>
                    <div class="stat-label">Periode Ini</div>
                </div>
            </td>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($stats['pil']) }}</div>
                    <div class="stat-label">Pil</div>
                </div>
            </td>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($stats['suntik']) }}</div>
                    <div class="stat-label">Suntik</div>
                </div>
            </td>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($stats['iud']) }}</div>
                    <div class="stat-label">IUD</div>
                </div>
            </td>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($stats['implan']) }}</div>
                    <div class="stat-label">Implan</div>
                </div>
            </td>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($stats['lainnya']) }}</div>
                    <div class="stat-label">Lainnya</div>
                </div>
            </td>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($stats['kunjungan_hari_ini']) }}</div>
                    <div class="stat-label">Kunj. Hari Ini</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- DATA TABLE -->
    <table class="data">
        <thead>
            <tr>
                <th width="3%">No</th>
                <th width="9%">Tgl Daftar</th>
                <th width="12%">No. Regis</th>
                <th width="18%">Nama Ibu</th>
                <th width="14%">Metode KB</th>
                <th width="11%">Kunj. Ulang</th>
                <th width="10%">Petugas</th>
                <th width="10%">No. HP</th>
                <th width="13%">Alamat</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($records as $i => $r)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        @php $tgl = Carbon::parse($r->tanggal); @endphp
                        {{ $tgl->format('d/m/Y') }}
                    </td>
                    <td>
                        <span class="badge info-badge">{{ $r->no_regis }}</span>
                    </td>
                    <td>
                        <div class="name">{{ $r->nama_istri }}</div>
                        @if ($r->umur_istri)
                            <div class="small-text">{{ $r->umur_istri }} tahun</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge success-badge">{{ $r->metode_kb }}</span>
                    </td>
                    <td>
                        {{ $r->tanggal_kunjungan_ulang ? Carbon::parse($r->tanggal_kunjungan_ulang)->format('d/m/Y') : '-' }}
                    </td>
                    <td>
                        {{ $r->user->name ?? '-' }}
                    </td>
                    <td>
                        {{ $r->no_hp ?? '-' }}
                    </td>
                    <td>
                        {{ Str::limit($r->alamat, 35) ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" align="center">
                        Tidak ada data KB
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        Total:
        <strong>{{ number_format($total) }}</strong>
        data |
        Dicetak:
        {{ $printed_at }}
    </div>

</body>

</html>
