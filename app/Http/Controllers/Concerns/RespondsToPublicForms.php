<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Invitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Dua endpoint tulis publik dipakai lewat fetch() maupun submit biasa. Bentuk
 * jawabannya harus sama-sama benar untuk keduanya: JSON bagi yang meminta JSON,
 * redirect-back bagi tamu yang JavaScript-nya gagal dimuat.
 *
 * `$scope` memisahkan pesan antar-form. RSVP dan ucapan berada pada satu
 * halaman, jadi tanpa pemisahan itu kegagalan salah satu form akan menampilkan
 * pesan merah di keduanya.
 */
trait RespondsToPublicForms
{
    protected function refuseWrite(Request $request, Invitation $invitation, string $scope): JsonResponse|RedirectResponse
    {
        $message = $invitation->status->rendersInvitation()
            ? 'Ini halaman demo. Kiriman tidak disimpan.'
            : 'Masa aktif undangan ini sudah berakhir, sehingga kiriman tidak lagi diterima.';

        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $message], 403);
        }

        return back()->withInput()->with("{$scope}_error", $message);
    }

    /** @param  array<string, mixed>  $payload */
    protected function acceptWrite(Request $request, string $scope, string $message, array $payload = []): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message] + $payload);
        }

        return back()->with("{$scope}_success", $message);
    }
}
