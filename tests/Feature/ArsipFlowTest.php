<?php

namespace Tests\Feature;

use App\Models\Tiket;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ArsipFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_open_arsip_index_and_show(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $tiket = Tiket::arsip()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('arsip.index'))
            ->assertOk()
            ->assertSee('Arsip Tahunan');

        $this->actingAs($admin)
            ->get(route('arsip.show', $tiket->id))
            ->assertOk()
            ->assertSee($tiket->no_tiket);
    }

    public function test_pimpinan_can_read_but_not_write(): void
    {
        $pimpinan = User::where('username', 'pimpinan')->firstOrFail();

        $this->actingAs($pimpinan)
            ->get(route('arsip.index'))
            ->assertOk();

        // Pimpinan boleh melihat halaman index; upaya POST arsip/restore harus 403 (hanya admin)
        $tiket = Tiket::aktif()->whereIn('status', ['selesai', 'batal'])->first();

        if ($tiket) {
            $this->actingAs($pimpinan)
                ->post(route('arsip.arsipkan', $tiket->id))
                ->assertForbidden();
        } else {
            // Semua tiket terimpor sudah diarsip; uji restore pun dilarang untuk pimpinan
            $tiketArsip = Tiket::arsip()->firstOrFail();
            $this->actingAs($pimpinan)
                ->post(route('arsip.restore', $tiketArsip->id))
                ->assertForbidden();
        }
    }

    public function test_arsip_restore_cycle(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $tiket = Tiket::arsip()->where('status', 'selesai')->firstOrFail();
        $noTiket = $tiket->no_tiket;
        $riwayatSebelum = $tiket->riwayatStatuses()->count();

        // restore: tiket pindah ke daftar aktif
        $this->actingAs($admin)
            ->post(route('arsip.restore', $tiket->id))
            ->assertRedirect(route('arsip.index'));

        $tiket->refresh();
        $this->assertFalse($tiket->is_arsip);
        $this->assertNull($tiket->diarsipkan_pada);
        $this->assertSame($noTiket, $tiket->no_tiket);
        $this->assertGreaterThan($riwayatSebelum, $tiket->riwayatStatuses()->count());

        // arsipkan lagi
        $this->actingAs($admin)
            ->post(route('arsip.arsipkan', $tiket->id))
            ->assertRedirect(route('arsip.index'));

        $tiket->refresh();
        $this->assertTrue($tiket->is_arsip);
        $this->assertNotNull($tiket->diarsipkan_pada);
        $this->assertNotNull($tiket->diarsipkan_oleh);
    }
}