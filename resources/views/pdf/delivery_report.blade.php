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
                    <h2>LAPORAN DATA PERSALINAN</h2>

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
        use App\Models\Delivery;

        $dari = $tanggal_dari ? Carbon::parse($tanggal_dari) : Carbon::now()->startOfMonth();
        $sampai = $tanggal_sampai ? Carbon::parse($tanggal_sampai)->endOfDay() : Carbon::now()->endOfDay();

        $periodeCount = $records->count();
        $hariIni = Delivery::whereDate('tanggal', Carbon::today())->count();
        $bulanIni = Delivery::whereMonth('tanggal', now()->month)->count();
        $withKeluhan = $records->whereNotNull('keluhan')->filter(fn($r) => !empty($r->keluhan))->count();
        $withTindakan = $records->whereNotNull('tindakan')->filter(fn($r) => !empty($r->tindakan))->count();
        $withAlamat = $records->filter(fn($r) => !empty($r->alamat))->count();
        $withPekerjaan = $records->filter(fn($r) => !empty($r->pekerjaan_istri) || !empty($r->pekerjaan_suami))->count();
        $bayiLahirCount = $records->filter(fn($r) => $r->bayi_lahir)->count();
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
                    <div class="stat-number">{{ number_format($periodeCount) }}</div>
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
                    <div class="stat-number">{{ number_format($withKeluhan) }}</div>
                    <div class="stat-label">Ada Keluhan</div>
                </div>
            </td>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($withTindakan) }}</div>
                    <div class="stat-label">Ada Tindakan</div>
                </div>
            </td>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($bayiLahirCount) }}</div>
                    <div class="stat-label">Bayi Lahir</div>
                </div>
            </td>
            <td width="12.5%">
                <div class="stat-card">
                    <div class="stat-number">{{ number_format($withAlamat) }}</div>
                    <div class="stat-label">Ada Alamat</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- DATA TABLE -->
    <table class="data">
        <thead>
            <tr>
                <th width="3%">No</th>
                <th width="9%">Tgl Persalinan</th>
                <th width="20%">Nama Ibu</th>
                <th width="18%">Nama Ayah</th>
                <th width="5%">Umur Ibu</th>
                <th width="5%">Umur Ayah</th>
                <th width="12%">Pekerjaan</th>
                <th width="10%">No. Telpon</th>
                <th width="18%">Keluhan / Tindakan</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($records as $i => $r)
                <tr>
                    <td>{{ $i + 1 }}</td>

                    <td>
                        @php $tgl = Carbon::parse($r->tanggal); @endphp
                        {{ $tgl->format('d/m/Y') }}
                        @if ($r->bayi_lahir)
                            <br>
                            <span class="badge success-badge"><i class="bi bi-check2-all" style="font-size:6pt;"></i>  Bayi Lahir</span>
                        @endif
                    </td>

                    <td>
                        <div class="name">{{ $r->nama_istri }}</div>
                        @if ($r->pekerjaan_istri)
                            <div class="small-text">{{ $r->pekerjaan_istri }}</div>
                        @endif
                    </td>

                    <td>
                        <div class="small-text">
                            {{ $r->nama_suami ?? '-' }}
                        </div>
                        @if ($r->pekerjaan_suami)
                            <div class="small-text">{{ $r->pekerjaan_suami }}</div>
                        @endif
                    </td>

                    <td align="center">
                        <span class="text-xs font-semibold {{ $r->umur_istri && $r->umur_istri < 20 ? 'text-rose-600' : 'text-gray-700' }}">
                            {{ $r->umur_istri ? $r->umur_istri . ' thn' : '-' }}
                        </span>
                    </td>

                    <td align="center">
                        <span class="text-xs font-semibold text-gray-700">
                            {{ $r->umur_suami ? $r->umur_suami . ' thn' : '-' }}
                        </span>
                    </td>

                    <td>
                        @if ($r->pekerjaan_istri || $r->pekerjaan_suami)
                            <div class="small-text">
                                @if ($r->pekerjaan_istri) Ibu: {{ $r->pekerjaan_istri }} @endif
                                @if ($r->pekerjaan_suami)
                                    @if ($r->pekerjaan_istri) <br> @endif
                                    Ayah: {{ $r->pekerjaan_suami }}
                                @endif
                            </div>
                        @else
                            <span class="small-text">-</span>
                        @endif
                    </td>

                    <td>
                        {{ $r->no_telpon ?? '-' }}
                    </td>

                    <td>
                        @if ($r->keluhan)
                            <span class="small-text"><i class="bi bi-chat-left-quote text-gray-400 mr-1"></i>{{ Str::limit($r->keluhan, 55) }}</span>
                        @endif
                        @if ($r->tindakan)
                            @if ($r->keluhan) <br> @endif
                            <span class="small-text"><i class="bi bi-bandaid text-gray-400 mr-1"></i>{{ Str::limit($r->tindakan, 55) }}</span>
                        @endif
                        @if (!$r->keluhan && !$r->tindakan)
                            <span class="small-text">-</span>
                        @endif
                    </td>
                </tr>

            @empty
                <tr>
                    <td colspan="9" align="center">
                        Tidak ada data persalinan
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
