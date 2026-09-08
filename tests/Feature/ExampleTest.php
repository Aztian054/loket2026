<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test dasar yang tidak menyentuh database.
     * CATATAN: sengaja TIDAK memakai RefreshDatabase agar menjalankan suite
     * tidak pernah mengeksekusi migrate:fresh yang menghapus data impor asli.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);

        $responseTracking = $this->get('/tracking');
        $responseTracking->assertStatus(200);

        $responseApiStats = $this->get('/api/v1/stats');
        $responseApiStats->assertStatus(200);
    }
}
