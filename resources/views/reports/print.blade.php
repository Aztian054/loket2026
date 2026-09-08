<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Laporan Pelayanan — Kantor Pertanahan Kota Bandar Lampung</title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 11px; color: #000; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 12px; }
        .header h3 { margin: 0; font-size: 15px; text-transform: uppercase; }
        .header h4 { margin: 2px 0; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.grid { border: 1px solid #000; }
        table.grid th, table.grid td { border: 1px solid #000; padding: 5px; }
        table.grid th { background: #f0f0f0; text-align: center; }
        .no-print { margin-bottom: 15px; text-align: right; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 8px 16px; font-weight: bold; cursor: pointer; background: #0b2239; color:#fff; border:none; border-radius:4px;">
            🖨️ Cetak / Simpan PDF
        </button>
    </div>

    <div class="header">
        <h3>Kementerian Agraria dan Tata Ruang / Badan Pertanahan Nasional</h3>
        <h4>Kantor Pertanahan Kota Bandar Lampung</h4>
        <p>LAPORAN REKAPITULASI PELAYANAN LOKET & MONITORING BERKAS</p>
        <p style="font-size: 10px;">Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
    </div>

    <table class="grid">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 100px;">No. Tiket</th>
                <th style="width: 70px;">Tgl Masuk</th>
                <th>Nama Pemohon</th>
                <th>Jenis Layanan</th>
                <th style="width: 50px;">Bidang</th>
                <th style="width: 70px;">Target SLA</th>
                <th style="width: 80px;">Status</th>
                <th style="width: 80px;">Tgl Selesai</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tikets as $idx => $t)
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td style="font-weight: bold;">{{ $t->no_tiket }}</td>
                    <td style="text-align: center;">{{ $t->tanggal_masuk->format('d/m/Y') }}</td>
                    <td>{{ $t->nama_pemohon }}</td>
                    <td>{{ $t->jenisPermohonan->kode }} - {{ $t->jenisPermohonan->nama }}</td>
                    <td style="text-align: center;">{{ $t->bidangTanahs->count() }}</td>
                    <td style="text-align: center;">{{ $t->tanggal_target_selesai?->format('d/m/Y') ?? '-' }}</td>
                    <td style="text-align: center; text-transform: uppercase; font-weight: bold;">{{ $t->status }}</td>
                    <td style="text-align: center;">{{ $t->tanggal_selesai?->format('d/m/Y') ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px;">Tidak ada data permohonan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table style="width: 100%; margin-top: 30px;">
        <tr>
            <td style="width: 60%;"></td>
            <td style="width: 40%; text-align: center;">
                <div>Bandar Lampung, {{ now()->format('d F Y') }}</div>
                <div>Kepala Kantor Pertanahan Kota Bandar Lampung</div>
                <div style="height: 60px;"></div>
                <div>( <strong>........................................................</strong> )</div>
                <div>NIP. ....................................................</div>
            </td>
        </tr>
    </table>

</body>
</html>
