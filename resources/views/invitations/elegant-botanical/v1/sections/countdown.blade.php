@php
    $primaryEvent = $invitation->primaryEvent();
@endphp
<section id="countdown">
    <div class="kicker reveal">Save The Date</div>
    <h2 class="section-title reveal">Menuju Hari Bahagia</h2>

    @include('invitations._shared.partials.countdown', ['event' => $primaryEvent])

    @if ($primaryEvent)
        {{-- File .ics dibuat server, bukan Blob di JavaScript: iOS Safari menolak
             sebagian Blob download dan tamu berakhir dengan file kosong. --}}
        <a class="btn reveal" href="{{ route('invitation.ics', $invitation->slug) }}">
            <span aria-hidden="true">&#128197;</span> Simpan ke Kalender
        </a>
    @endif
</section>
