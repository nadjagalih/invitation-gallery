<section id="events">
    <div class="kicker reveal">Wedding Event</div>
    <h2 class="section-title reveal">Rangkaian Acara</h2>
    <p class="muted reveal">Tanpa mengurangi rasa hormat, kami mengundang Bapak/Ibu/Saudara/i untuk hadir pada acara berikut:</p>

    @foreach ($invitation->events as $event)
        <div class="event-card reveal">
            <div class="ev-title">{{ $event->title }}</div>
            <div class="ev-date">{{ $event->dateLabel() }}</div>
            <div class="ev-time">{{ $event->timeLabel() }}</div>
            <div class="ev-place">
                <strong>{{ $event->venue_name }}</strong><br>
                {!! nl2br(e($event->address)) !!}
            </div>
            @if ($event->notes)
                <div class="ev-notes">{{ $event->notes }}</div>
            @endif
            @if ($event->maps_url)
                <a class="btn" href="{{ $event->maps_url }}" target="_blank" rel="noopener nofollow">
                    <span aria-hidden="true">&#128205;</span> Lihat Lokasi
                </a>
            @endif
        </div>
    @endforeach
</section>
