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
            border-bottom: 3px solid #2563eb;
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
            background: #2563eb;
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
            color: #1e40af;
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
            border: 1px solid #dbeafe;
            background: #eff6ff;
            border-radius: 6px;
            text-align: center;
            padding: 10px 5px;
        }

        .stat-number {
            font-size: 16pt;
            font-weight: bold;
            color: #1d4ed8;
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
            background: #1e40af;
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

        .vaccine-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 7.5pt;
            font-weight: bold;
        }

        .vaccine-badge-vaksin {
            background: #eff6ff;
            color: #1d4ed8;
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

        .baby-tag {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            background: #fef9c3;
            color: #854d0e;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 9pt;
            font-weight: bold;
        }

        .small-text {
            color: #6b7280;
            font-size: 7pt;
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
                <div class="logo-icon">♥</div>
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
                    <h2>LAPORAN DATA IMUNISASI</h2>

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

        $dari = $tanggal_dari ? Carbon::parse($tanggal_dari) : Carbon::now()->startOfMonth();
        $sampai = $tanggal_sampai ? Carbon::parse($tanggal_sampai)->endOfDay() : Carbon::now()->endOfDay();

        $totalVaksin = $records->pluck('jenis_imunisasi')->unique()->count();

        $babyCount = $records->filter(function ($r) {
            $dob = $r->tanggal_lahir_pasien ? Carbon::parse($r->tanggal_lahir_pasien) : ($r->pasien?->tanggal_lahir ? Carbon::parse($r->pasien->tanggal_lahir) : null);
            return $dob && $dob->age < 2;
        })->count();

        $withParent  = $records->filter(fn($r) => !empty($r->nama_orang_tua))->count();
        $withAlamat  = $records->filter(fn($r) => !empty($r->alamat))->count();
        $withPengob  = $records->filter(fn($r) => !empty($r->pengobatan))->count();
        $withKet     = $records->filter(fn($r) => !empty($r->keterangan))->count();
        $hariIni     = \App\Models\Imunisasi::whereDate('tanggal_imunisasi', Carbon::today())->count();
        $bulanIni    = \App\Models\Imunisasi::whereMonth('tanggal_imunisasi', now()->month)->count();
    @endphp

    <!-- STATISTICS -->
    <table class="stats-table">
        <tr>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($total) }}</div>
                    <div class="stat-label">Total Data</div>
                </div>
            </td>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($records->count()) }}</div>
                    <div class="stat-label">Periode Ini</div>
                </div>
            </td>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($hariIni) }}</div>
                    <div class="stat-label">Hari Ini</div>
                </div>
            </td>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($bulanIni) }}</div>
                    <div class="stat-label">Bulan Ini</div>
                </div>
            </td>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($totalVaksin) }}</div>
                    <div class="stat-label">Jenis Vaksin</div>
                </div>
            </td>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($babyCount) }}</div>
                    <div class="stat-label">Bayi (&lt;2 thn)</div>
                </div>
            </td>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($withParent) }}</div>
                    <div class="stat-label">Ada Nama Orang Tua</div>
                </div>
            </td>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($withKet) }}</div>
                    <div class="stat-label">Ada Keterangan</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- DATA TABLE -->
    <table class="data">
        <thead>
            <tr>
                <th width="3%">No</th>
                <th width="10%">Tgl Imunisasi</th>
                <th width="22%">Nama Pasien</th>
                <th width="6%">Kelamin</th>
                <th width="5%">Umur</th>
                <th width="16%">Jenis Imunisasi</th>
                <th width="18%">Nama Orang Tua / Alamat</th>
                <th width="10%">Petugas</th>
                <th width="10%">Keterangan</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($records as $i => $r)
                @php
                    $dobRaw = $r->pasien?->tanggal_lahir ?? ($r->tanggal_lahir_pasien ?: null);
                    $dob    = $dobRaw ? Carbon::parse($dobRaw) : null;
                    $age    = $dob ? $dob->age . ' thn' : '-';
                @endphp

                <tr>
                    <td>{{ $i + 1 }}</td>

                    <td>
                        @php $tgl = Carbon::parse($r->tanggal_imunisasi); @endphp
                        {{ $tgl->format('d/m/Y') }}
                    </td>

                    <td>
                        <div class="name">
                            {{ $r->pasien?->nama ?? 'N/A' }}
                        </div>
                        <div class="nik">
                            @if ($r->pasien?->tanggal_lahir)
                                Lahir: {{ $r->pasien->tanggal_lahir->format('d/m/Y') }}
                            @elseif ($r->tanggal_lahir_pasien)
                                Lahir: {{ \Carbon\Carbon::parse($r->tanggal_lahir_pasien)->format('d/m/Y') }}
                            @else
                                -
                            @endif
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

                    <td align="center">
                        <span class="text-xs font-semibold text-gray-700">{{ $age }}</span>
                    </td>

                    <td>
                        <span class="vaccine-badge vaccine-badge-vaksin">
                            <i class="bi bi-shield-fill-check mr-1" style="font-size:6pt;"></i>{{ $r->jenis_imunisasi }}
                        </span>
                    </td>

                    <td>
                        @if ($r->nama_orang_tua)
                            <span class="small-text">
                                <i class="bi bi-person-fill text-gray-400 mr-1"></i>{{ $r->nama_orang_tua }}
                            </span>
                        @endif
                        @if ($r->alamat)
                            @if ($r->nama_orang_tua) <br> @endif
                            <span class="small-text">
                                <i class="bi bi-pin-map-fill text-gray-400 mr-1"></i>{{ Str::limit($r->alamat, 38) }}
                            </span>
                        @endif
                        @if (! $r->nama_orang_tua && ! $r->alamat)
                            <span class="small-text">-</span>
                        @endif
                    </td>

                    <td>
                        {{ $r->user?->name ?? 'N/A' }}
                    </td>

                    <td>
                        @if ($r->keterangan)
                            <span class="small-text">{{ Str::limit($r->keterangan, 45) }}</span>
                        @elseif ($r->pengobatan)
                            <span class="small-text"><i class="bi bi-capsule text-gray-400 mr-1"></i>{{ Str::limit($r->pengobatan, 45) }}</span>
                        @else
                            <span class="small-text">-</span>
                        @endif
                    </td>
                </tr>

            @empty
                <tr>
                    <td colspan="9" align="center">
                        Tidak ada data imunisasi
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
