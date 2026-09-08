<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lembar Ceklis Berkas — {{ $tiket->no_tiket }}</title>
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
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header h3 { margin: 0; font-size: 15px; text-transform: uppercase; }
        .header h4 { margin: 2px 0; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
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
        <button onclick="window.print()" style="padding: 8px 16px; font-weight: bold; cursor: pointer; background: #0b2239; color:#fff; border:none; border-radius:4px;">
            🖨️ Cetak Lembar Ceklis
        </button>
    </div>

    <div class="header">
        <h3>Kantor Pertanahan Kota Bandar Lampung</h3>
        <h4>LEMBAR KENDALI & CEKLIS KELENGKAPAN BERKAS FISIK</h4>
    </div>

    <table style="margin-bottom: 15px;">
        <tr>
            <td style="width: 20%;"><strong>Nomor Tiket</strong></td>
            <td style="width: 30%;">: <span style="font-size: 14px; font-weight: bold;">{{ $tiket->no_tiket }}</span></td>
            <td style="width: 20%;"><strong>Tanggal Masuk</strong></td>
            <td style="width: 30%;">: {{ $tiket->tanggal_masuk->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><strong>Nama Pemohon</strong></td>
            <td>: {{ $tiket->nama_pemohon }}</td>
        </tr>
        <tr>
            <td><strong>Jenis Layanan</strong></td>
            <td colspan="3">: <strong>{{ $tiket->jenisPermohonan->kode }} &bull; {{ $tiket->jenisPermohonan->nama }}</strong></td>
        </tr>
    </table>

    <div style="font-weight: bold; margin-bottom: 6px;">Daftar Kelengkapan Dokumen:</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 30px; text-align: center;">No</th>
                <th>Nama Dokumen / Persyaratan</th>
                <th style="width: 80px; text-align: center;">Ada</th>
                <th style="width: 80px; text-align: center;">Tidak Ada</th>
                <th style="width: 150px;">Catatan Fisik</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tiket->jenisPermohonan->persyaratanDokumens as $idx => $req)
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td>{{ $req->nama_dokumen }}</td>
                    <td style="text-align: center;">[ &nbsp; ]</td>
                    <td style="text-align: center;">[ &nbsp; ]</td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="font-weight: bold; margin-top: 15px; margin-bottom: 6px;">Catatan Jalur Kendali Petugas:</div>
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 110px;">Tahapan</th>
                <th style="width: 90px;">Status</th>
                <th style="width: 130px;">Petugas</th>
                <th style="width: 100px;">Tanggal Selesai</th>
                <th>Catatan / Paraf</th>
            </tr>
        </thead>
        <tbody>
            @php
                use App\Models\Tiket;

                // Verifikator
                $verifLabel   = Tiket::labelStatusStage($tiket->status_verifikator);
                $verifTanggal = $tiket->latestVerifikasi?->tanggal_selesai?->format('d/m/Y');
                $verifPetugas = $tiket->latestVerifikasi?->verifikator?->name;
                $verifCatatan = $tiket->latestVerifikasi?->catatan;

                // Warkah
                $warkahs       = $tiket->lembarKerjaWarkahs;
                $warkahLabel   = Tiket::labelStatusStage($tiket->status_warkah);
                $warkahTanggal = $warkahs->map->tanggal_selesai->filter()->max() ? $warkahs->map->tanggal_selesai->filter()->max()->format('d/m/Y') : null;
                $warkahPetugas = $warkahs->whereNotNull('petugas_id')->first()?->petugas?->name;
                $warkahCatatan = $warkahs->whereNotNull('catatan')->first()?->catatan;
            @endphp
            <tr>
                <td><strong>1. Loket</strong></td>
                <td style="text-align: center;"><strong style="color: #006600;">SELESAI</strong></td>
                <td>{{ $tiket->petugasLoket->name ?? 'Petugas Loket' }}</td>
                <td>{{ $tiket->tanggal_masuk->format('d/m/Y') }}</td>
                <td>Pendaftaran berkas selesai</td>
            </tr>
            <tr>
                <td><strong>2. Verifikator</strong></td>
                <td style="text-align: center;">{{ $verifLabel }}</td>
                <td>{{ $verifPetugas ?? '' }}</td>
                <td>{{ $verifTanggal ?? '' }}</td>
                <td>{{ $verifCatatan ?? '' }}</td>
            </tr>
            <tr>
                <td><strong>3. Warkah</strong></td>
                <td style="text-align: center;">{{ $warkahLabel }}</td>
                <td>{{ $warkahPetugas ?? '' }}</td>
                <td>{{ $warkahTanggal ?? '' }}</td>
                <td>{{ $warkahCatatan ?? '' }}</td>
            </tr>
            <tr>
                <td><strong>4. Validator</strong></td>
                <td style="text-align: center;">{{ Tiket::labelStatusStage($tiket->lembarKerjaValidasis->isEmpty() ? null : ($tiket->isValidasiLulus() ? 'selesai' : 'proses')) }}</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td><strong>5. Alih Media</strong></td>
                <td style="text-align: center;">{{ Tiket::labelStatusStage($tiket->status_alih_media) }}</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>

</body>
</html>
