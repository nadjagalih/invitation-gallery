{{--
  Daftar ucapan yang sudah disetujui, dipaginasi. Form ucapan terpisah dari
  RSVP supaya tamu yang sudah mengisi RSVP tetap bisa menambah doa.

  Umpan balik dirender dua jalur seperti pada form RSVP: [data-form-msg] diisi
  JavaScript, dan tanpa JavaScript diisi session flash + error bag `wishes`.

  @var \App\Models\Invitation $invitation
  @var \Illuminate\Contracts\Pagination\Paginator $wishes
  @var string $guestName
  @var bool $acceptsWrites
--}}
@php
    use App\Support\FormState;

    $honeypot = config('invitation.honeypot_field');
    $timestampField = config('invitation.timestamp_field');

    $wishErrors = $errors->getBag('wishes');
    $flashSuccess = session('wishes_success');
    $flashError = session('wishes_error') ?? $wishErrors->first();

    $defaultName = $guestName === config('invitation.guest_name_fallback') ? '' : $guestName;
    $nameValue = FormState::old('wishes', 'name', $defaultName);
    $messageValue = FormState::old('wishes', 'message', '');
@endphp

@if ($acceptsWrites)
    <form class="wish-form reveal" method="POST" action="{{ route('invitation.wishes', $invitation->slug) }}"
          data-async-form data-reset-on-success data-prepend-target="[data-wish-list]">
        @csrf
        <input type="hidden" name="{{ FormState::FIELD }}" value="wishes">

        <div class="hp-field" aria-hidden="true">
            <label for="wish-{{ $honeypot }}">Website</label>
            <input type="text" id="wish-{{ $honeypot }}" name="{{ $honeypot }}" tabindex="-1" autocomplete="off">
        </div>
        <input type="hidden" name="{{ $timestampField }}" value="{{ encrypt(now()->timestamp) }}">

        <div class="field @error('name', 'wishes') has-error @enderror">
            <label for="wish-name">Nama</label>
            <input type="text" id="wish-name" name="name" value="{{ $nameValue }}"
                   placeholder="Nama Anda" maxlength="80" required>
            @error('name', 'wishes')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field @error('message', 'wishes') has-error @enderror">
            <label for="wish-message">Ucapan &amp; Doa</label>
            <textarea id="wish-message" name="message" maxlength="1000" required
                      placeholder="Tuliskan doa dan ucapan terbaik Anda...">{{ $messageValue }}</textarea>
            @error('message', 'wishes')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-block">Kirim Ucapan</button>

        <div class="form-msg @if ($flashSuccess) ok @elseif ($flashError) err @endif"
             data-form-msg role="status" aria-live="polite">{{ $flashSuccess ?? $flashError }}</div>
    </form>
@endif

<div class="wish-list reveal" data-wish-list>
    @forelse ($wishes as $wish)
        <div class="wish-item">
            <div class="w-name">{{ $wish->name }}</div>
            <div class="w-text">{{ $wish->message }}</div>
            <span class="w-time">{{ $wish->created_at->diffForHumans() }}</span>
        </div>
    @empty
        <p class="wish-empty">Belum ada ucapan. Jadilah yang pertama mengirim doa.</p>
    @endforelse
</div>

@if ($wishes->hasPages())
    <div class="wish-pagination">
        {{-- fragment() membuat tautan mendarat di bagian ucapan, bukan di puncak halaman. --}}
        {{ $wishes->fragment('wishes')->links('invitations._shared.partials.pagination') }}
    </div>
@endif
