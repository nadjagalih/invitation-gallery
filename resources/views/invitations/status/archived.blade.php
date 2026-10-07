@extends('invitations.status.layout')

@section('content')
    <div class="mark" aria-hidden="true">&#128188;</div>
    <h1>Undangan Telah Diarsipkan</h1>
    <div class="couple">{{ $invitation->coupleNames() }}</div>

    <p>
        Terima kasih atas doa dan restu yang telah diberikan kepada
        {{ $invitation->coupleNames() }}.
    </p>
    <p>
        Halaman undangan ini sudah diarsipkan dan berkasnya tidak lagi tersimpan,
        sehingga tidak dapat ditampilkan maupun dipulihkan kembali.
    </p>

    <div class="divider" aria-hidden="true"></div>

    <a class="btn outline" href="{{ url('/') }}">Lihat Katalog Undangan</a>
@endsection
