<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_welcome_text_and_footer(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Selamat Datang Kembali!');
        $response->assertSee('Silakan masuk untuk melanjutkan');
        $response->assertSee('PT Taman Wisata Candi Borobudur, Prambanan dan Ratu Boko (Persero) © 2026. All Rights Reserved.');
    }

    public function test_dashboard_renders_filter_bar_before_kpis_and_shows_footer(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Venue Availability');
        $response->assertSee('PT Taman Wisata Candi Borobudur, Prambanan dan Ratu Boko (Persero) © 2026. All Rights Reserved.');

        $content = $response->getContent();
        $filterPos = strpos($content, 'filters-bar');
        $kpiPos = strpos($content, 'class="kpis"');

        $this->assertNotFalse($filterPos);
        $this->assertNotFalse($kpiPos);
        $this->assertTrue($filterPos < $kpiPos, 'Filters bar should appear before KPI cards');
    }

    public function test_event_list_page_has_no_terapkan_button_and_shows_footer(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/bookings');

        $response->assertStatus(200);
        $response->assertSee('Daftar Event');
        $response->assertDontSee('Terapkan');
        $response->assertSee('Reset');
        $response->assertSee('PT Taman Wisata Candi Borobudur, Prambanan dan Ratu Boko (Persero) © 2026. All Rights Reserved.');
    }
}
