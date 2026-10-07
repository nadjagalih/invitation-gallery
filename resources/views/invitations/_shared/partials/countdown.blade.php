{{--
  Countdown. Angka awal dicetak server-side supaya tidak ada kedipan "00" saat
  pemuatan; JavaScript hanya melanjutkan hitungannya per detik.

  @var \App\Models\InvitationEvent|null $event
--}}
@php
    $target = $event?->starts_at;
    $remaining = $target ? max(0, now()->diffInSeconds($target, false)) : 0;
    $initial = [
        'days' => intdiv($remaining, 86400),
        'hours' => intdiv($remaining % 86400, 3600),
        'mins' => intdiv($remaining % 3600, 60),
        'secs' => $remaining % 60,
    ];
@endphp

<div class="cd-grid reveal" data-countdown="{{ $target?->toIso8601String() }}">
    @foreach (['days' => 'Hari', 'hours' => 'Jam', 'mins' => 'Menit', 'secs' => 'Detik'] as $key => $label)
        <div class="cd-cell">
            <div class="cd-num" data-cd="{{ $key }}">{{ str_pad((string) $initial[$key], 2, '0', STR_PAD_LEFT) }}</div>
            <div class="cd-label">{{ $label }}</div>
        </div>
    @endforeach
</div>
