<?php

namespace Tests\Feature;

use App\Models\BidangTanah;
use App\Models\JenisPermohonan;
use App\Models\LembarKerjaAlihMedia;
use App\Models\LembarKerjaValidasi;
use App\Models\Notifikasi;
use App\Models\Tiket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RevisiFinalWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected function user(string $username): User
    {
        return User::where('username', $username)->firstOrFail();
    }

    protected function makeTiket(string $status): Tiket
    {
        $jp = JenisPermohonan::firstOrFail();
        $tiket = Tiket::create([
            'no_tiket' => 'T-' . uniqid(),
            'status_pembetulan' => 'P0',
            'tanggal_masuk' => Carbon::today(),
            'jenis_permohonan_id' => $jp->id,
            'nama_pemohon' => 'Pemohon Uji',
            'nik_pemohon' => null,
            'no_hp_pemohon' => '081234567890',
            'jumlah_bidang' => 1,
            'petugas_loket_id' => $this->user('loket1')->id,
            'status' => $status,
            'status_verifikator' => 'selesai',
            'status_warkah' => 'selesai',
            'keterangan' => null,
            'tanggal_target_selesai' => Carbon::today()->addDays(10),
        ]);
        $tiket->bidangTanahs()->create(['urutan' => 1]);
        return $tiket;
    }

    public function test_loket_store_minimal_creates_default_bidang(): void
    {
        $jp = JenisPermohonan::firstOrFail();
        $noTiket = 'R-' . uniqid();

        $response = $this->actingAs($this->user('loket1'))
            ->post(route('loket.store'), [
                'no_tiket' => $noTiket,
                'jenis_permohonan_id' => $jp->id,
                'nama_pemohon' => 'Budi Santoso',
                'nik_pemohon' => '1871000000000001',
                'no_hp_pemohon' => '081298765432',
                'keterangan' => 'Registrasi minimalis.',
                'status_awal' => 'diterima',
            ]);

        $response->assertRedirect();
        $tiket = Tiket::where('no_tiket', $noTiket)->firstOrFail();
        $this->assertEquals(1, $tiket->bidangTanahs()->count());
    }

    public function test_validator_gate_and_requires_both_sub_roles(): void
    {
        $btel = $this->user('validator_btel');
        $suel = $this->user('validator_suel');
        $tiket = $this->makeTiket('validasi');
        $bidang = $tiket->bidangTanahs->first();

        // BTel completes
        $this->actingAs($btel)
            ->post(route('validator.update', $tiket->id), [
                'validator_btel_id' => $btel->id,
                'action_type' => 'save_draft',
                'items' => [['bidang_id' => $bidang->id, 'status_pra_btel' => 'selesai']],
            ])->assertSessionHasNoErrors();

        $lk = LembarKerjaValidasi::where('tiket_id', $tiket->id)->where('bidang_id', $bidang->id)->firstOrFail();
        $this->assertSame('selesai', $lk->status_pra_btel);
        $this->assertSame('proses', $lk->status_validasi_bidang);

        // SuEl completes
        $this->actingAs($suel)
            ->post(route('validator.update', $tiket->id), [
                'validator_suel_id' => $suel->id,
                'action_type' => 'save_draft',
                'items' => [['bidang_id' => $bidang->id, 'status_pra_suel' => 'selesai']],
            ])->assertSessionHasNoErrors();

        $lk->refresh();
        $this->assertSame('lulus', $lk->status_validasi_bidang);
    }

    public function test_validator_sub_role_only_writes_own_sub_bidang(): void
    {
        $btel = $this->user('validator_btel');
        $tiket = $this->makeTiket('validasi');
        $bidang = $tiket->bidangTanahs->first();

        $this->actingAs($btel)
            ->post(route('validator.update', $tiket->id), [
                'validator_btel_id' => $btel->id,
                'action_type' => 'save_draft',
                'items' => [['bidang_id' => $bidang->id, 'status_pra_btel' => 'selesai']],
            ])->assertSessionHasNoErrors();

        $lk = LembarKerjaValidasi::where('tiket_id', $tiket->id)->firstOrFail();
        $this->assertSame('selesai', $lk->status_pra_btel);
        $this->assertNull($lk->validator_suel_id);
        $this->assertSame('belum', $lk->status_pra_suel);
    }

    public function test_alih_media_gate_and_final(): void
    {
        $btel = $this->user('alih_media_btel');
        $suel = $this->user('alih_media_suel');
        $tiket = $this->makeTiket('alih_media');
        $bidang = $tiket->bidangTanahs->first();

        LembarKerjaAlihMedia::create([
            'tiket_id' => $tiket->id, 'bidang_id' => $bidang->id,
            'tanggal_mulai' => Carbon::today(),
        ]);

        // BTel completes first
        $this->actingAs($btel)
            ->post(route('alih_media.update', $tiket->id), [
                'petugas_btel_id' => $btel->id,
                'action_type' => 'complete_btel',
                'items' => [['bidang_id' => $bidang->id, 'catatan' => null]],
            ])->assertSessionHasNoErrors();

        $tiket->refresh();
        $this->assertSame('alih_media', $tiket->status);

        // SuEl completes → both done → SELESAI
        $this->actingAs($suel)
            ->post(route('alih_media.update', $tiket->id), [
                'petugas_suel_id' => $suel->id,
                'action_type' => 'complete_suel',
                'items' => [['bidang_id' => $bidang->id, 'catatan' => null]],
            ])->assertSessionHasNoErrors();

        $tiket->refresh();
        $this->assertSame('selesai', $tiket->status);
        $this->assertNotNull($tiket->tanggal_selesai);
    }

    public function test_notification_sub_bidang_isolation(): void
    {
        $btel = $this->user('validator_btel');
        $suel = $this->user('validator_suel');
        $tiket = $this->makeTiket('validasi');

        Notifikasi::create([
            'tiket_id' => $tiket->id, 'role_tujuan' => 'validator',
            'sub_bidang' => 'pra_btel', 'tipe' => Notifikasi::TIPE_REVISI,
            'judul' => 'Revisi BTel', 'pesan' => '...',
            'link_role' => 'validator', 'butuh_konfirmasi' => true,
        ]);
        Notifikasi::create([
            'tiket_id' => $tiket->id, 'role_tujuan' => 'validator',
            'sub_bidang' => 'pra_suel', 'tipe' => Notifikasi::TIPE_REVISI,
            'judul' => 'Revisi SuEl', 'pesan' => '...',
            'link_role' => 'validator', 'butuh_konfirmasi' => true,
        ]);
        Notifikasi::create([
            'tiket_id' => $tiket->id, 'role_tujuan' => 'validator',
            'sub_bidang' => null, 'tipe' => Notifikasi::TIPE_FORWARD,
            'judul' => 'Umum', 'pesan' => '...',
            'link_role' => 'validator', 'butuh_konfirmasi' => false,
        ]);

        $btelTitles = Notifikasi::forUser($btel)->pluck('judul')->sort()->values()->all();
        $this->assertEqualsCanonicalizing(['Revisi BTel', 'Umum'], $btelTitles);

        $suelTitles = Notifikasi::forUser($suel)->pluck('judul')->sort()->values()->all();
        $this->assertEqualsCanonicalizing(['Revisi SuEl', 'Umum'], $suelTitles);
    }
}
