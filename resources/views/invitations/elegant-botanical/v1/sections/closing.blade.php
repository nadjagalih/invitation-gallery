<footer id="closing">
    <div class="kicker reveal">Thank You</div>
    <p class="reveal">
        {{ $invitation->closing_words
            ?: 'Merupakan suatu kebahagiaan dan kehormatan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir dan memberikan doa restu kepada kami.' }}
    </p>
    <div class="thanks-names reveal">{{ $invitation->coupleNames() }}</div>
    <div class="credit">Dibuat dengan &hearts; oleh {{ config('invitation.brand.name') }}</div>
</footer>
