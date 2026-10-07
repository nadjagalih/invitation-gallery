<?php

namespace App\Http\Controllers;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Support\GuestName;
use App\Support\IcsBuilder;
use App\Support\InvitationMeta;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\View;

class InvitationController extends Controller
{
    public function show(Request $request, string $slug): Response
    {
        $invitation = Invitation::query()
            ->with(['template', 'events', 'stories.media', 'bankAccounts.logo', 'media'])
            ->where('slug', $slug)
            ->first();

        abort_if($invitation === null, 404);

        // Draft belum boleh dilihat siapa pun kecuali pemegang signed link dari
        // panel admin. `to` dikecualikan dari tanda tangan supaya admin bisa
        // menguji tampilan nama tamu tanpa membuat link baru.
        if ($invitation->status === InvitationStatus::Draft
            && ! $request->hasValidSignatureWhileIgnoring(['to'])) {
            abort(404);
        }

        $guestName = GuestName::fromRequest($request);

        return match ($invitation->status) {
            InvitationStatus::Draft,
            InvitationStatus::Preview,
            InvitationStatus::Active => $this->renderInvitation($invitation, $guestName),
            InvitationStatus::Expired => $this->renderExpired($invitation),
            InvitationStatus::Archived => $this->renderArchived($invitation),
        };
    }

    public function ics(Request $request, string $slug): Response
    {
        $invitation = Invitation::query()->with('events')->where('slug', $slug)->first();

        abort_if($invitation === null, 404);
        abort_unless(
            $invitation->status->rendersInvitation() || $request->hasValidSignatureWhileIgnoring(['to']),
            404,
        );
        abort_if($invitation->events->isEmpty(), 404);

        return response(IcsBuilder::forInvitation($invitation), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.IcsBuilder::filenameFor($invitation).'"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    private function renderInvitation(Invitation $invitation, string $guestName): Response
    {
        $view = $invitation->viewName();

        if (! View::exists($view)) {
            // Kegagalan konfigurasi, bukan kesalahan tamu: pesannya harus
            // menyebut folder versi yang hilang agar langsung bisa dibetulkan.
            throw new \RuntimeException(
                "View template [{$view}] tidak ada. Undangan #{$invitation->id} menunjuk ke "
                ."template [{$invitation->template->slug}] versi {$invitation->template_version}."
            );
        }

        return response()->view($view, [
            'invitation' => $invitation,
            'guestName' => $guestName,
            'meta' => InvitationMeta::for($invitation, $guestName),
            'acceptsWrites' => $invitation->status->acceptsWrites(),
            'wishes' => $this->wishesFor($invitation),
        ]);
    }

    private function renderExpired(Invitation $invitation): Response
    {
        // Sengaja 200, bukan 410: undangan masih bisa diperpanjang, jadi ini
        // halaman informasi dengan CTA — bukan sumber daya yang hilang permanen.
        return response()->view('invitations.status.expired', [
            'invitation' => $invitation,
            'meta' => InvitationMeta::plain(
                'Masa Aktif Undangan Berakhir',
                'Masa aktif undangan ini sudah berakhir. Hubungi kami untuk memperpanjang.',
                $invitation->publicUrl(),
            ),
            'whatsappUrl' => $this->extensionWhatsappUrl($invitation),
        ]);
    }

    private function renderArchived(Invitation $invitation): Response
    {
        // 410 Gone: file undangan ini sudah dihapus dan tidak akan kembali.
        return response()->view('invitations.status.archived', [
            'invitation' => $invitation,
            'meta' => InvitationMeta::plain(
                'Undangan Telah Diarsipkan',
                'Undangan ini sudah diarsipkan.',
                $invitation->publicUrl(),
            ),
        ], 410);
    }

    private function wishesFor(Invitation $invitation): LengthAwarePaginator
    {
        $perPage = (int) config('invitation.wishes_per_page');

        if (! $invitation->hasFeature('wishes')) {
            return new LengthAwarePaginator([], 0, $perPage, 1, ['path' => request()->url()]);
        }

        return $invitation->wishes()
            ->where('is_approved', true)
            ->latest()
            ->paginate($perPage)
            // Tanpa ini tautan halaman 2 kehilangan ?to= dan tamu berubah
            // menjadi "Tamu Undangan" di tengah kunjungan.
            ->withQueryString();
    }

    private function extensionWhatsappUrl(Invitation $invitation): string
    {
        $text = "Halo, saya ingin memperpanjang masa aktif undangan {$invitation->coupleNames()} "
            ."({$invitation->slug}).";

        return 'https://wa.me/'.config('invitation.brand.whatsapp').'?text='.urlencode($text);
    }
}
