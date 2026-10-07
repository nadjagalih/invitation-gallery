@php
    use App\Enums\MediaCollection;
    use App\Support\InstagramProfile;

    $groomPhoto = $invitation->firstMediaIn(MediaCollection::GroomPhoto);
    $bridePhoto = $invitation->firstMediaIn(MediaCollection::BridePhoto);
    $groomIg = InstagramProfile::url($invitation->groom_instagram);
    $brideIg = InstagramProfile::url($invitation->bride_instagram);
@endphp
<section id="couple">
    <div class="kicker reveal">Kedua Mempelai</div>
    <h2 class="section-title reveal">Mempelai</h2>

    <div class="couple-wrap">
        <div class="reveal">
            <div class="person-photo">
                @if ($groomPhoto)
                    <img src="{{ $groomPhoto->url(480) }}" alt="Foto {{ $invitation->groom_nickname }}"
                         width="150" height="150" loading="lazy" decoding="async">
                @else
                    <span aria-hidden="true">{{ Str::substr($invitation->groom_nickname, 0, 1) }}</span>
                @endif
            </div>
            <div class="person-name">{{ $invitation->groom_nickname }}</div>
            <div class="person-full">{{ $invitation->groom_full_name }}</div>
            <div class="person-parents">{{ $invitation->groomParentsLine() }}</div>
            @if ($groomIg)
                <a class="ig-link" href="{{ $groomIg }}" target="_blank" rel="noopener nofollow">
                    <span aria-hidden="true">&#9737;</span> {{ InstagramProfile::label($invitation->groom_instagram) }}
                </a>
            @endif
        </div>

        <div class="amp reveal" aria-hidden="true">&amp;</div>

        <div class="reveal">
            <div class="person-photo">
                @if ($bridePhoto)
                    <img src="{{ $bridePhoto->url(480) }}" alt="Foto {{ $invitation->bride_nickname }}"
                         width="150" height="150" loading="lazy" decoding="async">
                @else
                    <span aria-hidden="true">{{ Str::substr($invitation->bride_nickname, 0, 1) }}</span>
                @endif
            </div>
            <div class="person-name">{{ $invitation->bride_nickname }}</div>
            <div class="person-full">{{ $invitation->bride_full_name }}</div>
            <div class="person-parents">{{ $invitation->brideParentsLine() }}</div>
            @if ($brideIg)
                <a class="ig-link" href="{{ $brideIg }}" target="_blank" rel="noopener nofollow">
                    <span aria-hidden="true">&#9737;</span> {{ InstagramProfile::label($invitation->bride_instagram) }}
                </a>
            @endif
        </div>
    </div>
</section>
