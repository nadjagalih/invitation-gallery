<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToPublicForms;
use App\Http\Requests\StoreRsvpRequest;
use App\Models\Invitation;
use App\Support\RequestFingerprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class RsvpController extends Controller
{
    use RespondsToPublicForms;

    public function store(StoreRsvpRequest $request, string $slug): JsonResponse|RedirectResponse
    {
        $invitation = Invitation::query()->where('slug', $slug)->first();

        abort_if($invitation === null, 404);

        // Hanya `active` yang menerima tulisan. `preview` dirender penuh tetapi
        // menolak — demo katalog dibuka banyak orang asing.
        if (! $invitation->status->acceptsWrites()) {
            return $this->refuseWrite($request, $invitation, 'rsvp');
        }

        $invitation->rsvps()->create([
            'name' => $request->string('name')->trim()->value(),
            'attendance' => $request->enum('attendance', \App\Enums\Attendance::class),
            'party_size' => $request->integer('party_size') ?: 1,
            'message' => $request->filled('message')
                ? $request->string('message')->trim()->value()
                : null,
            'ip_hash' => RequestFingerprint::ipHash($request),
            'user_agent' => RequestFingerprint::userAgent($request),
        ]);

        return $this->acceptWrite(
            $request,
            'rsvp',
            'Terima kasih, konfirmasi kehadiran Anda sudah kami terima.',
        );
    }
}
