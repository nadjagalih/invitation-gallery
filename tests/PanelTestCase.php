<?php

namespace Tests;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Dasar untuk test panel admin.
 *
 * Panel Filament hidup di belakang middleware yang menetapkan panel aktif dan
 * memverifikasi akses. Test komponen Livewire melewati middleware itu, jadi
 * keduanya dikerjakan tangan di sini: satu akun admin yang login dan panel
 * `admin` sebagai panel aktif.
 */
abstract class PanelTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();

        $this->actingAs($this->admin);

        Filament::setCurrentPanel('admin');
    }
}
