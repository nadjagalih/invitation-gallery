{{--
  Meta OG dicetak server-side. Preview link WhatsApp tidak menjalankan
  JavaScript, jadi tanpa ini tautan yang di-share tampil tanpa judul, tanpa nama
  pasangan, dan tanpa gambar.
--}}
<title>{{ $meta['title'] }}</title>
<meta name="description" content="{{ $meta['description'] }}">
<link rel="canonical" href="{{ $meta['url'] }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $meta['site_name'] }}">
<meta property="og:title" content="{{ $meta['title'] }}">
<meta property="og:description" content="{{ $meta['description'] }}">
<meta property="og:url" content="{{ $meta['url'] }}">
@if (! empty($meta['image']))
    <meta property="og:image" content="{{ $meta['image'] }}">
    <meta property="og:image:alt" content="{{ $meta['title'] }}">
@endif

<meta name="twitter:card" content="{{ empty($meta['image']) ? 'summary' : 'summary_large_image' }}">
<meta name="twitter:title" content="{{ $meta['title'] }}">
<meta name="twitter:description" content="{{ $meta['description'] }}">
@if (! empty($meta['image']))
    <meta name="twitter:image" content="{{ $meta['image'] }}">
@endif

{{-- Undangan bersifat privat bagi tamu yang menerima link; jangan diindeks. --}}
<meta name="robots" content="noindex, nofollow">
