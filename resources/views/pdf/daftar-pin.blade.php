<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar PIN Akun Orang Tua - Kelas {{ $namaKelas }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
            color: #1e293b;
            margin: 20px;
        }
        h2 {
            margin-bottom: 4px;
            color: #0f172a;
        }
        p {
            margin-top: 0;
            color: #64748b;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 7px 10px;
            text-align: left;
        }
        th {
            background-color: #0d9488;
            color: #ffffff;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .pin {
            font-family: monospace;
            font-weight: bold;
            font-size: 13px;
            color: #0f766e;
            letter-spacing: 2px;
        }
        .footer {
            margin-top: 25px;
            font-size: 10px;
            color: #64748b;
            font-style: italic;
        }
    </style>
</head>
<body>
    <h2>Daftar PIN Awal Akun Orang Tua</h2>
    <p>Kelas: <strong>{{ $namaKelas }}</strong> &middot; Tanggal Cetak: {{ date('d/m/Y H:i') }}</p>
    <p><em>Pemberitahuan: Simpan dan bagikan PIN ini secara tertutup ke wali murid masing-masing. PIN wajib diganti saat pertama kali login.</em></p>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">NIS</th>
                <th style="width: 25%;">Nama Siswa</th>
                <th style="width: 20%;">Nama Wali Murid</th>
                <th style="width: 18%;">Username</th>
                <th style="width: 17%;">PIN Awal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $index => $item)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $item['nis'] }}</td>
                    <td><strong>{{ $item['nama'] }}</strong></td>
                    <td>{{ $item['nama_ortu'] }}</td>
                    <td style="font-family: monospace;">{{ $item['username'] }}</td>
                    <td class="pin">{{ $item['pin'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak dari Sistem Absensi Siswa SD &middot; Harap jaga kerahasiaan data ini.
    </div>
</body>
</html>
