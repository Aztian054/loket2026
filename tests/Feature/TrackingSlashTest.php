<?php

namespace Tests\Feature;

use App\Models\Tiket;
use Tests\TestCase;

/** Regresi: deep-link /tracking/{no_tiket} harus bisa menangkap no_tiket berisi slash (mis. K/9/141024/1). */
class TrackingSlashTest extends TestCase
{
    public function test_tracking_deep_link_with_slash_in_no_tiket(): void
    {
        $tiket = Tiket::where('no_tiket', 'like', '%/%')->firstOrFail();
        $slug = rawurlencode($tiket->no_tiket);

        $this->get('/tracking/' . $slug)
            ->assertOk()
            ->assertSee($tiket->no_tiket);
    }

    public function test_tracking_search_by_no_tiket(): void
    {
        $tiket = Tiket::where('no_tiket', 'like', '%/%')->firstOrFail();

        $this->get('/tracking?no_tiket=' . rawurlencode($tiket->no_tiket))
            ->assertOk()
            ->assertSee($tiket->no_tiket);
    }
}