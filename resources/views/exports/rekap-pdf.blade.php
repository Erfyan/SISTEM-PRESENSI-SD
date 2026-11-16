<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Kehadiran - Kelas {{ $kelas->nama }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #0d9488;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
            color: #0f172a;
        }
        .header p {
            margin: 4px 0 0 0;
            font-size: 12px;
            color: #475569;
        }
        .meta {
            margin-bottom: 15px;
            width: 100%;
        }
        .meta td {
            font-size: 11px;
            padding: 3px 0;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table.data th, table.data td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: center;
        }
        table.data th {
            background-color: #0d9488;
            color: white;
            font-weight: bold;
        }
        table.data td.text-left {
            text-align: left;
        }
        table.data tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .footer {
            width: 100%;
            margin-top: 40px;
        }
        .footer td {
            text-align: center;
            font-size: 11px;
            vertical-align: top;
        }
        .signature-space {
            height: 60px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Rekapitulasi Presensi Siswa</h2>
        <p>Tahun Ajaran {{ $kelas->tahun_ajaran }}</p>
    </div>

    <table class="meta">
        <tr>
            <td width="15%"><strong>Kelas</strong></td>
            <td width="35%">: {{ $kelas->nama }}</td>
            <td width="20%"><strong>Bulan/Tahun</strong></td>
            <td width="30%">: {{ $namaBulan }}</td>
        </tr>
        <tr>
            <td><strong>Wali Kelas</strong></td>
            <td>: {{ $kelas->wali ? $kelas->wali->nama : '-' }}</td>
            <td><strong>Hari Efektif</strong></td>
            <td>: {{ $hariEfektif }} hari sekolah</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="12%">NIS</th>
                <th width="35%">Nama Siswa</th>
                <th width="6%">L/P</th>
                <th width="8%">Hadir</th>
                <th width="8%">Izin</th>
                <th width="8%">Sakit</th>
                <th width="8%">Alpa</th>
                <th width="10%">% Hadir</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rekap as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item['nis'] }}</td>
                    <td class="text-left">{{ $item['nama'] }}</td>
                    <td>{{ $item['jenis_kelamin'] }}</td>
                    <td>{{ $item['hadir'] }}</td>
                    <td>{{ $item['izin'] }}</td>
                    <td>{{ $item['sakit'] }}</td>
                    <td>{{ $item['alpa'] }}</td>
                    <td><strong>{{ $item['persentase'] }}%</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="padding: 20px;">Belum ada data siswa atau absensi pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="footer">
        <tr>
            <td width="50%">
                Mengetahui,<br>
                Kepala Sekolah
                <div class="signature-space"></div>
                <strong>( ............................................ )</strong><br>
                NIP. ........................................
            </td>
            <td width="50%">
                Dicetak pada: {{ date('d F Y') }}<br>
                Wali Kelas {{ $kelas->nama }}
                <div class="signature-space"></div>
                <strong>{{ $kelas->wali ? $kelas->wali->nama : '( ............................................ )' }}</strong><br>
                NIP. ........................................
            </td>
        </tr>
    </table>
</body>
</html>
