<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lembar Saran Koreksi — {{ $tiket->no_tiket }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            color: #000;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h3 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header h4 { margin: 3px 0; font-size: 14px; }
        .header p { margin: 2px 0; font-size: 11px; }
        .box-warning {
            border: 2px solid #b22222;
            background: #fff8f8;
            padding: 10px;
            text-align: center;
            margin-bottom: 15px;
        }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.data td { padding: 4px; vertical-align: top; }
        table.grid { border: 1px solid #000; }
        table.grid th, table.grid td { border: 1px solid #000; padding: 6px; }
        table.grid th { background: #f0f0f0; }
        .no-print { margin-bottom: 20px; text-align: right; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 8px 16px; font-weight: bold; cursor: pointer; background: #b22222; color:#fff; border:none; border-radius:4px;">
            🖨️ Cetak Lembar Saran Koreksi
        </button>
    </div>

    <div class="header">
        <h3>Kementerian Agraria dan Tata Ruang / Badan Pertanahan Nasional</h3>
        <h4>Kantor Pertanahan Kota Bandar Lampung</h4>
        <p>Jl. Basuki Rahmat No.12, Teluk Betung, Bandar Lampung</p>
    </div>

    <div class="box-warning">
        <h4 style="margin: 0; color: #b22222;">LEMBAR SARAN KOREKSI / PEMBERITAHUAN KEKURANGAN BERKAS</h4>
        <div style="font-size: 14px; font-weight: bold; margin-top: 5px;">Nomor Tiket: {{ $tiket->no_tiket }} (Iterasi: {{ $tiket->status_pembetulan }})</div>
    </div>

    <table class="data">
        <tr>
            <td style="width: 25%; font-weight: bold;">Kepada Yth.</td>
            <td style="width: 2%;">:</td>
            <td><strong>{{ $tiket->nama_pemohon }}</strong></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">No. Telepon / HP</td>
            <td>:</td>
            <td>{{ $tiket->no_hp_pemohon }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Jenis Permohonan</td>
            <td>:</td>
            <td>{{ $tiket->jenisPermohonan->kode }} - {{ $tiket->jenisPermohonan->nama }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Tanggal Pemeriksaan</td>
            <td>:</td>
            <td>{{ now()->format('d F Y') }}</td>
        </tr>
    </table>

    <p>Berdasarkan hasil pemeriksaan dan verifikasi kelengkapan dokumen berkas permohonan di atas, diberitahukan bahwa berkas permohonan Saudara dinyatakan <strong>BELUM LENGKAP / MEMERLUKAN PERBAIKAN</strong> dengan rincian sebagai berikut:</p>

    <div style="font-weight: bold; margin-bottom: 5px;">Rincian Kekurangan / Saran Perbaikan dari Verifikator:</div>
    <div style="border: 1px solid #000; padding: 12px; min-height: 60px; background: #fafafa; margin-bottom: 15px; white-space: pre-wrap; line-height: 1.5;">
        {{ $tiket->latestVerifikasi?->catatan ?? 'Harap melengkapi dokumen persyaratan sesuai lembar ceklis dan menyerahkan kembali ke Loket Pelayanan.' }}
    </div>

    <p style="font-size: 11px;">
        * Harap menyerahkan dokumen perbaikan tersebut ke <strong>Loket Pelayanan Kantah Kota Bandar Lampung</strong> dengan membawa lembar saran koreksi ini untuk diproses ke iterasi berikutnya.
    </p>

    <table style="width: 100%; margin-top: 30px;">
        <tr>
            <td style="width: 50%; text-align: center;">
                <div>Mengetahui,<br>Pemohon / Penerima Kuasa</div>
                <div style="height: 50px;"></div>
                <div>( <strong>{{ $tiket->nama_pemohon }}</strong> )</div>
            </td>
            <td style="width: 50%; text-align: center;">
                <div>Bandar Lampung, {{ now()->format('d/m/Y') }}<br>Petugas Verifikator Berkas,</div>
                <div style="height: 50px;"></div>
                <div>( <strong>{{ $tiket->latestVerifikasi?->verifikator->name ?? 'Petugas Verifikator' }}</strong> )</div>
            </td>
        </tr>
    </table>

</body>
</html>
