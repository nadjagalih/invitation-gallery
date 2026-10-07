<section id="story">
    <div class="kicker reveal">Our Story</div>
    <h2 class="section-title reveal">Kisah Kami</h2>

    <div>
        @foreach ($invitation->stories as $story)
            <div class="story-item reveal">
                <div class="dot" aria-hidden="true"></div>
                <div class="txt">
                    <div class="st-title">{{ $story->title }}</div>
                    @if ($story->dateLabel())
                        <div class="st-date">{{ $story->dateLabel() }}</div>
                    @endif
                    <div class="st-text">{!! nl2br(e($story->body)) !!}</div>
                    @if ($story->media)
                        <div class="st-photo">
                            <img src="{{ $story->media->url(480) }}" alt="{{ $story->title }}"
                                 loading="lazy" decoding="async">
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>
