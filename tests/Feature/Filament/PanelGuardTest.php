<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Terpisah dari PanelAccessTest karena di sana setiap test sudah login sebagai
 * admin, sedangkan yang diuji di sini justru keadaan sebelum itu.
 */
class PanelGuardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    #[Test]
    public function akun_bukan_admin_ditolak(): void
    {
        // Akun klien memakai tabel users yang sama; is_admin adalah satu-satunya
        // pembeda, dan itulah yang harus benar-benar menutup pintu.
        $client = User::factory()->create();

        $this->actingAs($client)->get('/admin')->assertForbidden();
    }
}
