<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToPublicForms;
use App\Http\Requests\StoreWishRequest;
use App\Models\Invitation;
use App\Support\RequestFingerprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class WishController extends Controller
{
    use RespondsToPublicForms;

    public function store(StoreWishRequest $request, string $slug): JsonResponse|RedirectResponse
    {
        $invitation = Invitation::query()->where('slug', $slug)->first();

        abort_if($invitation === null, 404);

        if (! $invitation->status->acceptsWrites()) {
            return $this->refuseWrite($request, $invitation, 'wishes');
        }

        // Default is_approved mengikuti moderate_wishes milik undangan, bukan
        // konstanta: sebagian klien ingin ucapan langsung tampil.
        $approved = ! $invitation->moderate_wishes;

        $wish = $invitation->wishes()->create([
            'name' => $request->string('name')->trim()->value(),
            'message' => $request->string('message')->trim()->value(),
            'is_approved' => $approved,
            'ip_hash' => RequestFingerprint::ipHash($request),
        ]);

        $message = $approved
            ? 'Terima kasih, ucapan Anda sudah tampil di bawah.'
            : 'Terima kasih, ucapan Anda akan tampil setelah disetujui.';

        return $this->acceptWrite($request, 'wishes', $message, [
            // Hanya ucapan yang sudah tampil dikirim balik untuk disisipkan ke
            // daftar; yang menunggu moderasi tidak boleh terlihat lebih dulu.
            'wish' => $approved ? [
                'name' => $wish->name,
                'message' => $wish->message,
                'time' => $wish->created_at->diffForHumans(),
            ] : null,
        ]);
    }
}
