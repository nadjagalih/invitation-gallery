{{-- @var \Illuminate\Support\Collection<int, \App\Models\InvitationMedia> $gallery --}}
<section id="gallery">
    <div class="kicker reveal">Moment</div>
    <h2 class="section-title reveal">Galeri Foto</h2>

    <div class="gal-grid reveal">
        @foreach ($gallery as $photo)
            @php
                $alt = 'Foto '.$invitation->coupleNames().' '.($loop->iteration);
            @endphp
            <button type="button" class="gal-item"
                    data-lightbox-src="{{ $photo->url(1440) }}"
                    data-lightbox-alt="{{ $alt }}"
                    aria-label="Perbesar {{ $alt }}">
                <img src="{{ $photo->url(480) }}"
                     @if ($photo->srcset()) srcset="{{ $photo->srcset() }}" sizes="(max-width: 480px) 33vw, 160px" @endif
                     alt="{{ $alt }}" loading="lazy" decoding="async">
            </button>
        @endforeach
    </div>
</section>
