@extends('invitations.status.layout')

@section('content')
    <div class="mark" aria-hidden="true">&#9203;</div>
    <h1>Masa Aktif Undangan Telah Berakhir</h1>
    <div class="couple">{{ $invitation->coupleNames() }}</div>

    <p>
        Terima kasih telah menjadi bagian dari hari bahagia
        {{ $invitation->coupleNames() }} pada {{ $invitation->eventDateLabel() }}.
    </p>
    <p>
        Halaman undangan ini sudah melewati masa aktifnya, sehingga isinya tidak
        lagi ditampilkan. Foto dan data masih kami simpan
        @if ($invitation->asset_delete_at)
            sampai {{ $invitation->asset_delete_at->translatedFormat('j F Y') }}
        @endif
        dan masih dapat diaktifkan kembali.
    </p>

    <div class="divider" aria-hidden="true"></div>

    <a class="btn" href="{{ $whatsappUrl }}" target="_blank" rel="noopener">
        <span aria-hidden="true">&#128172;</span> Perpanjang Masa Aktif
    </a>
@endsection
