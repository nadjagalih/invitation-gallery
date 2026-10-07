<section id="gift">
    <div class="kicker reveal">Wedding Gift</div>
    <h2 class="section-title reveal">Amplop Digital</h2>
    <p class="muted reveal">Doa restu Anda merupakan karunia yang sangat berarti bagi kami. Namun jika ingin memberikan tanda kasih, dapat melalui:</p>

    @foreach ($invitation->bankAccounts as $bank)
        <div class="bank-card reveal">
            @if ($bank->logo)
                <img class="bank-logo" src="{{ $bank->logo->url(480) }}" alt="{{ $bank->bank_name }}" loading="lazy" decoding="async">
            @endif
            <div class="bank-name">{{ $bank->bank_name }}</div>
            <div class="bank-number">{{ $bank->account_number }}</div>
            <div class="bank-owner">a.n. {{ $bank->account_holder }}</div>
            <button type="button" class="copy-btn"
                    data-copy="{{ $bank->account_number }}"
                    data-copy-success="&#10003; Tersalin!">
                <span aria-hidden="true">&#128203;</span> Salin Nomor
            </button>
        </div>
    @endforeach

    @if ($invitation->gift_address)
        <div class="addr-card reveal">
            <strong>Kirim Kado ke Alamat:</strong><br>
            {!! nl2br(e($invitation->gift_address)) !!}
            <div>
                <button type="button" class="copy-btn"
                        data-copy="{{ $invitation->gift_address }}"
                        data-copy-success="&#10003; Tersalin!">
                    <span aria-hidden="true">&#128203;</span> Salin Alamat
                </button>
            </div>
        </div>
    @endif
</section>
