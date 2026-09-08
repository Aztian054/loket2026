<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tanda Terima Permohonan — {{ $tiket->no_tiket }}</title>
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
        .header h3 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
        }
        .header h4 {
            margin: 3px 0;
            font-size: 14px;
        }
        .header p {
            margin: 2px 0;
            font-size: 11px;
        }
        .ticket-box {
            border: 2px solid #000;
            padding: 10px;
            text-align: center;
            margin-bottom: 15px;
            background: #fdfdfd;
        }
        .ticket-no {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data td {
            padding: 4px 6px;
            vertical-align: top;
        }
        table.grid {
            border: 1px solid #000;
        }
        table.grid th, table.grid td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }
        table.grid th {
            background-color: #eee;
        }
        .footer-signs {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
        }
        .sign-box {
            width: 40%;
            text-align: center;
        }
        .sign-space {
            height: 60px;
        }
        .no-print {
            margin-bottom: 20px;
            text-align: right;
        }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 8px 16px; font-weight: bold; cursor: pointer; background: #0b2239; color:#fff; border:none; border-radius:4px;">
            🖨️ Cetak Tanda Terima
        </button>
    </div>

    <div class="header">
        <h3>Kementerian Agraria dan Tata Ruang / Badan Pertanahan Nasional</h3>
        <h4>Kantor Pertanahan Kota Bandar Lampung</h4>
        <p>Jl. Basuki Rahmat No.12, Teluk Betung, Bandar Lampung &bull; Telp. (0721) 481234</p>
    </div>

    <div class="ticket-box">
        <div style="font-size: 11px; text-transform: uppercase;">Surat Tanda Penerimaan Berkas Permohonan</div>
        <div class="ticket-no">{{ $tiket->no_tiket }}</div>
        <div style="font-size: 11px;">Status Iterasi: <strong>{{ $tiket->status_pembetulan }}</strong> &bull; Tgl: {{ $tiket->tanggal_masuk->format('d/m/Y') }}</div>
    </div>

    <table class="data">
        <tr>
            <td style="width: 25%; font-weight: bold;">Nama Pemohon / Kuasa</td>
            <td style="width: 2%;">:</td>
            <td><strong>{{ $tiket->nama_pemohon }}</strong></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">NIK / Identitas</td>
            <td>:</td>
            <td>{{ $tiket->nik_pemohon ?? '-' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">No. Telepon / HP</td>
            <td>:</td>
            <td>{{ $tiket->no_hp_pemohon }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Jenis Pelayanan / Hak</td>
            <td>:</td>
            <td><strong>{{ $tiket->jenisPermohonan->kode }} - {{ $tiket->jenisPermohonan->nama }}</strong></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Standar Waktu Layanan</td>
            <td>:</td>
            <td>{{ $tiket->jenisPermohonan->batas_hari_sla }} Hari Kerja (Estimasi: {{ $tiket->tanggal_target_selesai?->format('d/m/Y') ?? '-' }})</td>
        </tr>
    </table>

    <div style="font-weight: bold; margin-bottom: 5px;">Rincian Bidang Tanah:</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th>NIB</th>
                <th>No. Sertifikat / Hak</th>
                <th>Jenis Hak</th>
                <th>Luas (m²)</th>
                <th>Kelurahan / Kecamatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tiket->bidangTanahs as $i => $b)
                <tr>
                    <td style="text-align: center;">{{ $i + 1 }}</td>
                    <td>{{ $b->nib ?? '-' }}</td>
                    <td>{{ $b->no_sertifikat_lama ?? '-' }}</td>
                    <td>{{ $b->jenis_hak ?? '-' }}</td>
                    <td>{{ $b->luas_m2 ? number_format($b->luas_m2, 2) : '-' }}</td>
                    <td>{{ $b->desa_kelurahan ?? '-' }}, {{ $b->kecamatan ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="border: 1px dashed #666; padding: 8px; font-size: 11px; margin-top: 10px;">
        <strong>Perhatian:</strong>
        <ol style="margin: 3px 0 0 15px; padding: 0;">
            <li>Tanda terima ini merupakan bukti sah pendaftaran berkas permohonan di Kantor Pertanahan Kota Bandar Lampung.</li>
            <li>Anda dapat memantau status berkas secara online melalui link: <strong>{{ url('/tracking/' . urlencode($tiket->no_tiket)) }}</strong></li>
            <li>Apabila terdapat kekurangan berkas fisik, petugas verifikator akan menerbitkan lembar saran koreksi.</li>
        </ol>
    </div>

    <table style="width: 100%; margin-top: 25px;">
        <tr>
            <td style="width: 50%; text-align: center;">
                <div>Pemohon / Kuasa,</div>
                <div class="sign-space"></div>
                <div>( <strong>{{ $tiket->nama_pemohon }}</strong> )</div>
            </td>
            <td style="width: 50%; text-align: center;">
                <div>Bandar Lampung, {{ $tiket->tanggal_masuk->format('d/m/Y') }}<br>Petugas Loket Penerima,</div>
                <div class="sign-space"></div>
                <div>( <strong>{{ $tiket->petugasLoket->name ?? 'Petugas Loket' }}</strong> )</div>
            </td>
        </tr>
    </table>

</body>
</html>
