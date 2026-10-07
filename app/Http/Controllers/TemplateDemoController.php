<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\RedirectResponse;

/**
 * Tombol "Lihat" pada kartu katalog. Redirect, bukan render langsung, supaya
 * URL yang tersimpan di riwayat browser tamu adalah URL undangan demo — dan
 * demo bisa dipindah ke undangan lain tanpa mengubah tautan katalog.
 */
class TemplateDemoController extends Controller
{
    public function __invoke(Template $template): RedirectResponse
    {
        abort_unless($template->is_active, 404);

        $demo = $template->demoInvitation;

        abort_if($demo === null, 404);

        return redirect()->route('invitation.show', $demo->slug);
    }
}
