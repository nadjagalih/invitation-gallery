{{--
  Form RSVP. Submit sungguhan ke endpoint POST, bukan demo di memori halaman.
  Honeypot dan timestamp ikut dikirim; keduanya divalidasi di server.

  Semua umpan balik dirender dua kali jalur: JavaScript mengisi [data-form-msg]
  lewat fetch(), sedangkan tanpa JavaScript nilainya datang dari session flash
  dan error bag `rsvp`. Tamu yang JavaScript-nya gagal dimuat tetap tahu apakah
  kirimannya masuk.

  @var \App\Models\Invitation $invitation
  @var string $guestName
  @var bool $acceptsWrites
--}}
@php
    use App\Support\FormState;

    $honeypot = config('invitation.honeypot_field');
    $timestampField = config('invitation.timestamp_field');
    $maxPartySize = (int) config('invitation.rsvp_max_party_size');

    $rsvpErrors = $errors->getBag('rsvp');
    $flashSuccess = session('rsvp_success');
    $flashError = session('rsvp_error') ?? $rsvpErrors->first();

    // Nama tamu dari ?to= dipakai sebagai nilai awal, tetapi old input menang
    // supaya koreksi tamu tidak terhapus saat validasi gagal.
    $defaultName = $guestName === config('invitation.guest_name_fallback') ? '' : $guestName;
    $nameValue = FormState::old('rsvp', 'name', $defaultName);
    $attendanceValue = FormState::old('rsvp', 'attendance', \App\Enums\Attendance::cases()[0]->value);
    $partySizeValue = (int) FormState::old('rsvp', 'party_size', 1);
    $messageValue = FormState::old('rsvp', 'message', '');
@endphp

@if (! $acceptsWrites)
    <div class="form-notice reveal">
        @if ($invitation->status === \App\Enums\InvitationStatus::Preview)
            Ini halaman demo. Form konfirmasi kehadiran tidak aktif di sini.
        @else
            Konfirmasi kehadiran sudah ditutup.
        @endif
    </div>
@else
    <form class="reveal" method="POST" action="{{ route('invitation.rsvp', $invitation->slug) }}" data-async-form data-reset-on-success>
        @csrf
        <input type="hidden" name="{{ FormState::FIELD }}" value="rsvp">

        {{-- Honeypot: disembunyikan dari manusia, menarik bagi bot. --}}
        <div class="hp-field" aria-hidden="true">
            <label for="{{ $honeypot }}">Website</label>
            <input type="text" id="{{ $honeypot }}" name="{{ $honeypot }}" tabindex="-1" autocomplete="off">
        </div>
        <input type="hidden" name="{{ $timestampField }}" value="{{ encrypt(now()->timestamp) }}">

        <div class="field @error('name', 'rsvp') has-error @enderror">
            <label for="rsvp-name">Nama</label>
            <input type="text" id="rsvp-name" name="name" value="{{ $nameValue }}"
                   placeholder="Nama lengkap Anda" maxlength="80" required>
            @error('name', 'rsvp')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field @error('attendance', 'rsvp') has-error @enderror">
            <label>Konfirmasi Kehadiran</label>
            <div class="radio-row">
                @foreach (\App\Enums\Attendance::cases() as $case)
                    <label>
                        <input type="radio" name="attendance" value="{{ $case->value }}" @checked($attendanceValue === $case->value)>
                        <span>{{ $case->label() }}</span>
                    </label>
                @endforeach
            </div>
            @error('attendance', 'rsvp')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field @error('party_size', 'rsvp') has-error @enderror">
            <label for="rsvp-party-size">Jumlah Tamu</label>
            <select id="rsvp-party-size" name="party_size">
                @for ($n = 1; $n <= $maxPartySize; $n++)
                    <option value="{{ $n }}" @selected($partySizeValue === $n)>{{ $n }} Orang</option>
                @endfor
            </select>
            @error('party_size', 'rsvp')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field @error('message', 'rsvp') has-error @enderror">
            <label for="rsvp-message">Ucapan &amp; Doa <span class="optional">(opsional)</span></label>
            <textarea id="rsvp-message" name="message" maxlength="1000"
                      placeholder="Tuliskan doa dan ucapan terbaik Anda...">{{ $messageValue }}</textarea>
            @error('message', 'rsvp')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-block">Kirim Konfirmasi</button>

        <div class="form-msg @if ($flashSuccess) ok @elseif ($flashError) err @endif"
             data-form-msg role="status" aria-live="polite">{{ $flashSuccess ?? $flashError }}</div>
    </form>
@endif
