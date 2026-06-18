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
                    <h2>LAPORAN DATA PASIEN</h2>

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
        use App\Models\Pasien;
        use Illuminate\Support\Carbon;

        $dari = $tanggal_dari ? Carbon::parse($tanggal_dari) : Carbon::now()->startOfMonth();

        $sampai = $tanggal_sampai ? Carbon::parse($tanggal_sampai)->endOfDay() : Carbon::now()->endOfDay();

        $stats = [
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

        $statCells = [
            [$stats['total_semua'], 'Total Pasien'],
            [$stats['total_dari_sampai'], 'Periode Ini'],
            [$stats['jumlah_laki'], 'Laki-laki'],
            [$stats['jumlah_perempuan'], 'Perempuan'],
            [$stats['dewasa'], 'Dewasa'],
            [$stats['anak_anak'], 'Anak-anak'],
            [$stats['ibu_hamil'], 'Ibu Hamil'],
            [$stats['bayi'], 'Bayi'],
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
                <th width="20%">Nama Pasien</th>
                <th width="10%">Kelamin</th>
                <th width="14%">Jenis Pasien</th>
                <th width="15%">Lahir / Umur</th>
                <th width="14%">Telepon</th>
                <th width="10%">Gol. Darah</th>
                <th width="13%">Tgl Daftar</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($pasiens as $i => $p)
                @php
                    $dob = $p->tanggal_lahir;
                    $age = $dob ? Carbon::parse($dob)->age . ' thn' : '-';
                @endphp

                <tr>
                    <td>{{ $i + 1 }}</td>

                    <td>
                        <div class="name">
                            {{ $p->nama }}
                        </div>

                        <div class="nik">
                            {{ $p->nik ?? '-' }}
                        </div>
                    </td>

                    <td align="center">
                        @if ($p->jenis_kelamin == 'L')
                            <span class="badge male">L</span>
                        @else
                            <span class="badge female">P</span>
                        @endif
                    </td>

                    <td>
                        <span class="badge patient-type">
                            {{ $p->jenis_pasien ?? '-' }}
                        </span>
                    </td>

                    <td>
                        {{ $dob ? Carbon::parse($dob)->format('d/m/Y') : '-' }}
                        <br>
                        <span class="small-text">
                            {{ $age }}
                        </span>
                    </td>

                    <td>
                        {{ $p->no_telpon ?? '-' }}
                    </td>

                    <td align="center">
                        {{ strtoupper($p->golongan_darah ?? '-') }}
                    </td>

                    <td>
                        {{ Carbon::parse($p->created_at)->format('d/m/Y') }}
                    </td>
                </tr>

            @empty
                <tr>
                    <td colspan="8" align="center">
                        Tidak ada data pasien
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        Total:
        <strong>{{ number_format($total) }}</strong>
        pasien |
        Dicetak:
        {{ $printed_at }}
    </div>

</body>

</html>
