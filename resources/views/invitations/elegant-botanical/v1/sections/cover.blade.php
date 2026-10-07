@php
    $coverUrl = $invitation->coverUrl();
    $coverStyles = array_filter([
        $coverUrl ? "--cover-image: url('{$coverUrl}')" : null,
        // Tamu yang mendarat dari tautan paginasi ucapan sudah "membuka" undangan.
        $autoOpen ? 'display: none' : null,
    ]);
@endphp
<section id="cover" data-cover @if ($coverStyles) style="{{ implode('; ', $coverStyles) }}" @endif>
    <div>
        <div class="eyebrow">The Wedding Of</div>
        <div class="names">{{ $invitation->coupleNames() }}</div>
        <div class="date">{{ $invitation->eventDateLabel() }}</div>
    </div>

    <div class="guest-box">
        <small>Kepada Yth. Bapak/Ibu/Saudara/i</small>
        <div class="guest-name">{{ $guestName }}</div>
    </div>

    <button id="open-btn" type="button" data-open-invitation>
        <span aria-hidden="true">&#9993;</span> Buka Undangan
    </button>
</section>
