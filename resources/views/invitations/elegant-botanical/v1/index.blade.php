{{--
  ELEGANT BOTANICAL — versi 1

  Folder versi ini read-only setelah ada undangan yang memakainya. Perbaikan
  desain yang mengubah tata letak masuk ke v2, bukan ke sini, supaya undangan
  yang sudah tersebar tidak berubah tampilannya di tengah masa aktif.

  Kontrak data dari controller:
  @var \App\Models\Invitation $invitation
  @var string                 $guestName      sudah disanitasi GuestName
  @var array                  $meta           dari InvitationMeta::for()
  @var bool                   $acceptsWrites  hanya true saat status active
  @var \Illuminate\Contracts\Pagination\Paginator $wishes
--}}
@extends('invitations._shared.layout')

@php
    // Tamu yang menekan tautan halaman ucapan mendarat di ?page=2. Tanpa ini
    // mereka disuruh menekan "Buka Undangan" lagi dari awal. Hal yang sama
    // berlaku setelah submit tanpa JavaScript: pesan hasilnya ada di dalam,
    // jadi undangan harus sudah terbuka saat halaman dimuat kembali.
    $autoOpen = request()->filled('page')
        || \App\Support\FormState::hasFeedback('rsvp', 'wishes');
    $musicUrl = $invitation->hasFeature('music') ? $invitation->musicUrl() : null;
    $gallery = $invitation->hasFeature('gallery')
        ? $invitation->mediaIn(\App\Enums\MediaCollection::Gallery)
        : collect();
@endphp

@section('theme-color', '#8c5b4b')

@section('fonts')
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Great+Vibes&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
@endsection

@section('styles')
@include('invitations.elegant-botanical.v1.styles')
@endsection

@section('body')
<div id="frame">

    @include('invitations.elegant-botanical.v1.sections.cover', ['autoOpen' => $autoOpen])

    <div id="main" class="{{ $autoOpen ? 'show' : '' }}" data-main>
        @include('invitations.elegant-botanical.v1.sections.intro')
        @include('invitations.elegant-botanical.v1.sections.couple')
        @include('invitations.elegant-botanical.v1.sections.countdown')
        @include('invitations.elegant-botanical.v1.sections.events')

        @if ($invitation->hasFeature('love_story') && $invitation->stories->isNotEmpty())
            @include('invitations.elegant-botanical.v1.sections.story')
        @endif

        @if ($gallery->isNotEmpty())
            @include('invitations.elegant-botanical.v1.sections.gallery', ['gallery' => $gallery])
        @endif

        @if ($invitation->hasFeature('gift') && ($invitation->bankAccounts->isNotEmpty() || $invitation->gift_address))
            @include('invitations.elegant-botanical.v1.sections.gift')
        @endif

        @if ($invitation->hasFeature('rsvp'))
            @include('invitations.elegant-botanical.v1.sections.rsvp')
        @endif

        @if ($invitation->hasFeature('wishes'))
            @include('invitations.elegant-botanical.v1.sections.wishes')
        @endif

        @include('invitations.elegant-botanical.v1.sections.closing')
    </div>

    @if ($musicUrl)
        <button id="music-btn" class="{{ $autoOpen ? 'show' : '' }}" type="button"
                data-music-toggle aria-label="Putar atau hentikan musik">
            <span class="disc" aria-hidden="true">&#127925;</span>
        </button>
    @endif

    <nav id="bottom-nav" class="{{ $autoOpen ? 'show' : '' }}" data-bottom-nav>
        <a href="#intro"><span class="ic" aria-hidden="true">&#127968;</span>Home</a>
        <a href="#events"><span class="ic" aria-hidden="true">&#128197;</span>Acara</a>
        @if ($invitation->hasFeature('gift'))
            <a href="#gift"><span class="ic" aria-hidden="true">&#127873;</span>Kado</a>
        @endif
        @if ($invitation->hasFeature('rsvp'))
            <a href="#rsvp"><span class="ic" aria-hidden="true">&#9993;</span>RSVP</a>
        @endif
    </nav>

    @if ($gallery->isNotEmpty())
        <div id="lightbox" data-lightbox role="dialog" aria-modal="true" aria-label="Pratinjau foto">
            <span class="close-lb" data-lightbox-close role="button" aria-label="Tutup">&times;</span>
            <img data-lightbox-img src="" alt="">
        </div>
    @endif

    @if ($musicUrl)
        <audio loop preload="none" data-bg-audio>
            <source src="{{ $musicUrl }}" type="{{ $invitation->firstMediaIn(\App\Enums\MediaCollection::Music)?->mime ?: 'audio/mpeg' }}">
        </audio>
    @endif

</div>
@endsection
