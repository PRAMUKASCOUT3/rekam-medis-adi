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

        .patient-type {
            background: #f3f4f6;
            color: #374151;
        }

        .small-text {
            color: #6b7280;
            font-size: 7pt;
        }

        .stock-ok {
            background: #d1fae5;
            color: #065f46;
        }

        .stock-low {
            background: #fef3c7;
            color: #b45309;
        }

        .stock-out {
            background: #fee2e2;
            color: #b91c1c;
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
                    <h2>LAPORAN DATA OBAT</h2>

                    <div class="report-meta">
                        @if ($jenis)
                            Jenis:
                            <strong>{{ $jenis }}</strong>
                            <br>
                        @endif

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

    <!-- STATISTICS -->
    <table class="stats-table">
        <tr>
            @php
                $cells = [
                    [$total,              'Total Jenis Obat'],
                    [$total_stok,         'Total Stok'],
                    [$stok_aman,          'Stok Aman (&ge;10)'],
                    [$stok_menipis,       'Stok Menipis (&lt;10)'],
                    [$stok_habis,         'Stok Habis (0)'],
                ];
            @endphp
            @foreach ($cells as [$val, $label])
                <td width="20%">
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
                <th width="12%">Kode Obat</th>
                <th width="24%">Nama Obat</th>
                <th width="10%">Jenis</th>
                <th width="8%">Satuan</th>
                <th width="12%">Stok</th>
                <th width="22%">Tgl Dibuat</th>
                <th width="8%">Status</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($obats as $i => $o)
                @php
                    $s          = (int) ($o['stok'] ?? 0);
                    $stockClass = $s === 0 ? 'stock-out' : ($s < 10 ? 'stock-low' : 'stock-ok');
                    $stockLabel = $s === 0 ? 'HABIS' : ($s < 10 ? 'MENIPIS' : 'AMAN');
                @endphp

                <tr>
                    <td>{{ $i + 1 }}</td>

                    <td>
                        <span class="nik">{{ $o['kode'] }}</span>
                    </td>

                    <td>
                        <div class="name">
                            {{ $o['nama'] }}
                        </div>
                    </td>

                    <td>
                        <span class="badge patient-type">
                            {{ strtoupper($o['type']) }}
                        </span>
                    </td>

                    <td>
                        {{ $o['satuan'] }}
                    </td>

                    <td>
                        {{ number_format($s) }}
                    </td>

                    <td>
                        {{ $o['created_at'] }}
                    </td>

                    <td>
                        <span class="badge {{ $stockClass }}">
                            {{ $stockLabel }}
                        </span>
                    </td>
                </tr>

            @empty
                <tr>
                    <td colspan="8" align="center">
                        Tidak ada data obat
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        Total:
        <strong>{{ number_format($total) }}</strong>
        obat |
        Total stok:
        <strong>{{ number_format($total_stok) }}</strong>
        unit |
        Dicetak:
        {{ $printed_at }}
    </div>

</body>
</html>
