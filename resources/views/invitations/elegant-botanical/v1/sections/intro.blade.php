<section id="intro">
    <div class="kicker reveal">We Are</div>
    <h2 class="section-title reveal">Getting Married</h2>

    @if ($invitation->opening_words)
        <p class="muted reveal">{!! nl2br(e($invitation->opening_words)) !!}</p>
    @endif

    @if ($invitation->quote_text)
        <div class="quote-box reveal">
            <p class="quote-text">&ldquo;{{ $invitation->quote_text }}&rdquo;</p>
            @if ($invitation->quote_source)
                <span class="quote-source">{{ $invitation->quote_source }}</span>
            @endif
        </div>
    @endif
</section>
