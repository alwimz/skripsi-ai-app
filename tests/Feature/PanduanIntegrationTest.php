<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanduanIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_public_panduan_pages(): void
    {
        $this->get('/panduan')
            ->assertStatus(200)
            ->assertSee('Panduan Lengkap Menyusun Skripsi dengan SkripsiLab AI');

        $this->get('/panduan/cari-judul-skripsi')
            ->assertStatus(200)
            ->assertSee('Panduan Cari Judul Skripsi Otomatis');

        $this->get('/panduan/slug-yang-tidak-ada')
            ->assertStatus(404);
    }

    public function test_authenticated_user_on_panduan_urls_is_kept_inside_workspace(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/panduan')
            ->assertRedirect(route('app.create', ['tab' => 'panduan']) . '#panduan');

        $this->actingAs($user)
            ->get('/panduan/cari-judul-skripsi')
            ->assertRedirect(route('app.create', ['tab' => 'panduan', 'guide' => 'cari-judul-skripsi']) . '#panduan');
    }

    public function test_authenticated_dashboard_contains_in_app_panduan_and_no_external_panduan_tab(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/app/create');

        $response->assertStatus(200);
        $response->assertSee('id="nav-panduan"', false);
        $response->assertSee('id="pane-panduan"', false);
        $response->assertSee('Panduan Lengkap Workspace', false);
        $response->assertSee('Panduan Cari Judul Skripsi Otomatis Sesuai Jurusan &amp; 9 Metode Penelitian', false);
        $response->assertDontSee('route(\'panduan.index\')', false);

        $html = $response->getContent();
        $this->assertStringContainsString('</html>', $html);
        $this->assertStringContainsString('function toggleSidebar()', $html);
        $this->assertStringContainsString('function submitReferralWithdraw(', $html);
        $this->assertSame(
            preg_match_all('/<script\b/i', $html),
            preg_match_all('/<\/script>/i', $html),
            'Every <script> tag in dashboard HTML must be closed properly.'
        );
    }

    public function test_landing_page_routes_authenticated_users_to_internal_panduan_tab(): void
    {
        $guestHtml = $this->get('/')->assertStatus(200)->getContent();
        $this->assertStringContainsString('/panduan"', $guestHtml);

        $user = User::factory()->create();
        $authHtml = $this->actingAs($user)->get('/')->assertStatus(200)->getContent();
        $this->assertStringContainsString('tab=panduan#panduan', $authHtml);
    }
}
