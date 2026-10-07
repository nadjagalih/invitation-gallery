<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Katalog undangan pernikahan digital Lensaku Invitation.">
    <title>Lensaku Invitation - Katalog Undangan Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <style>
        :root { --paper: #f5f4f0; --ink: #1c1e1d; --muted: #5b605d; --line: #dad8d1; --green: #34493d; --white: #fff; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; background: var(--paper); color: var(--ink); font-family: "DM Sans", sans-serif; font-size: 16px; line-height: 1.6; }
        a { color: inherit; text-decoration: none; }
        a:focus-visible, button:focus-visible { outline: 2px solid var(--green); outline-offset: 3px; }
        .wrap { width: min(1200px, calc(100% - 64px)); margin: 0 auto; }
        .serif { font-family: "Instrument Serif", serif; }
        .kicker { margin: 0; color: var(--muted); font-size: 10px; letter-spacing: .18em; text-transform: uppercase; }
        .btn { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 0 22px; border-radius: 999px; font-size: 15px; font-weight: 500; transition: transform .2s ease, background .2s ease; }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background: var(--green); color: var(--white); }
        .btn-secondary { border: 1px solid #bdbab1; color: var(--ink); }
        .btn-light { min-height: 48px; padding: 0 28px; background: var(--white); color: var(--ink); }
        .site-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; padding-top: 24px; padding-bottom: 24px; }
        .brand { font-size: 26px; }
        .site-nav { display: flex; align-items: center; gap: 28px; flex-wrap: wrap; font-size: 15px; }
        .site-nav a:not(.btn):hover, .footer-nav a:hover { color: var(--green); }
        .hero { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 56px; padding-top: 64px; padding-bottom: 96px; }
        .hero-copy { flex: 1 1 420px; max-width: 560px; }
        .hero-copy h1 { margin: 20px 0 0; font-size: clamp(44px, 6vw, 76px); font-weight: 400; line-height: 1.02; }
        .hero-copy h1 em { font-style: italic; }
        .hero-copy > p:not(.kicker) { max-width: 460px; margin: 24px 0 36px; color: var(--muted); font-size: 18px; }
        .hero-actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .hero-device { flex: 1 1 320px; display: flex; justify-content: center; }
        .phone { display: flex; width: 280px; height: 540px; align-items: center; justify-content: center; flex-direction: column; gap: 16px; padding: 24px; border: 10px solid var(--ink); border-radius: 40px; background: var(--white); text-align: center; }
        .phone-title { font-size: 52px; line-height: 1; }
        .phone-title small { display: block; font-size: 30px; font-style: italic; }
        .phone .btn { margin-top: 12px; padding: 0 24px; font-size: 14px; }
        .catalog { padding-bottom: 96px; }
        .catalog-heading { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 24px; margin-bottom: 40px; }
        .catalog-heading h2, .info-heading h2 { margin: 12px 0 0; font-size: clamp(34px, 4vw, 48px); font-weight: 400; line-height: 1.1; }
        .filters { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
        .filter { display: inline-flex; align-items: center; min-height: 44px; padding: 0 20px; border: 1px solid var(--line); border-radius: 999px; background: transparent; color: var(--ink); font: 500 14px "DM Sans", sans-serif; cursor: pointer; }
        .filter:hover, .filter.active { border-color: var(--ink); background: var(--ink); color: var(--white); }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 48px 32px; }
        .product { display: flex; flex-direction: column; gap: 18px; }
        .preview { display: flex; height: 340px; align-items: center; justify-content: center; border-radius: 20px; }
        .mini-phone { display: flex; width: 150px; height: 260px; align-items: center; justify-content: center; flex-direction: column; gap: 10px; padding: 18px; border-radius: 20px; box-shadow: 0 18px 40px rgba(28, 30, 29, .12); text-align: center; }
        .mini-phone img { width: 100%; height: 100%; border-radius: 14px; object-fit: cover; }
        .mini-title { font-size: 30px; line-height: 1.05; }
        .product-meta { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
        .product-meta h3 { margin: 0; font-size: 26px; font-weight: 400; }
        .product-meta p { margin: 4px 0 0; color: var(--muted); font-size: 14px; }
        .price { padding-top: 6px; font-size: 14px; font-weight: 500; text-align: right; white-space: nowrap; }
        .old-price { display: block; color: var(--muted); text-decoration: line-through; }
        .product-actions { display: flex; gap: 10px; }
        .product-actions a, .product-actions span { flex: 1; }
        .product-actions .btn { width: 100%; }
        .empty { grid-column: 1 / -1; padding: 48px; border: 1px dashed var(--line); border-radius: 20px; color: var(--muted); text-align: center; }

        .info-heading { max-width: 560px; margin-bottom: 48px; }
        .features { padding: 96px 0; background: var(--white); }
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 40px 32px; }
        .feature h3, .step h3 { margin: 12px 0 8px; font-size: 18px; }
        .feature p:not(.kicker), .step p { margin: 0; color: var(--muted); }
        .steps { padding-top: 96px; padding-bottom: 96px; }
        .step-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 32px; }
        .step { padding-top: 20px; border-top: 1px solid #bdbab1; }
        .step-number { font-size: 40px; line-height: 1; }
        .cta { padding-bottom: 96px; }
        .cta-band { display: flex; align-items: center; flex-direction: column; gap: 24px; padding: 72px 32px; border-radius: 28px; background: var(--ink); color: var(--white); text-align: center; }
        .cta-band h2 { margin: 0; max-width: 640px; font-size: clamp(34px, 4.5vw, 56px); font-weight: 400; line-height: 1.1; }
        .cta-band p { max-width: 460px; margin: 0; color: #c9c7c1; }

        .footer { border-top: 1px solid var(--line); padding: 24px 0 36px; }
        .footer-inner { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px 24px; color: var(--muted); font-size: 14px; }
        .footer-nav { display: flex; gap: 24px; }
        @media (max-width: 700px) {
            .wrap { width: min(100% - 32px, 1200px); }
            .site-header { align-items: flex-start; }
            .site-nav { gap: 14px; font-size: 14px; }
            .site-nav .btn { display: none; }
            .hero { gap: 40px; padding-top: 34px; padding-bottom: 68px; }
            .hero-copy > p:not(.kicker) { font-size: 16px; }
            .hero-device { order: -1; }
            .phone { width: 230px; height: 440px; }
            .phone-title { font-size: 42px; }
            .catalog-heading { align-items: flex-start; }
            .filters { justify-content: flex-start; overflow-x: auto; flex-wrap: nowrap; max-width: 100%; padding-bottom: 4px; }
            .filter { flex: 0 0 auto; }
            .features, .steps { padding-top: 64px; padding-bottom: 64px; }
            .cta { padding-bottom: 64px; }
            .cta-band { padding: 56px 24px; }
            .footer-inner { align-items: flex-start; flex-direction: column; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .001ms !important; } }
    </style>
</head>
<body>
<header class="wrap site-header">
    <a class="brand serif" href="{{ route('catalog.index') }}">Lensaku <em>Invitation</em></a>
    <nav class="site-nav" aria-label="Navigasi utama">
        <a href="#katalog">Katalog</a><a href="#fitur">Fitur</a><a href="#cara">Cara Pesan</a>
        <a class="btn btn-primary" href="{{ $whatsappUrl }}?text={{ rawurlencode('Halo, saya ingin pesan undangan digital.') }}">Pesan via WhatsApp</a>
    </nav>
</header>

<main>
    <section class="wrap hero">
        <div class="hero-copy">
            <p class="kicker">Undangan pernikahan digital</p>
            <h1 class="serif">Undangan yang sederhana, <em>untuk hari yang berarti.</em></h1>
            <p>Pilih template, kirim data Anda, dan bagikan lewat satu tautan. Tanpa cetak, tanpa ribet.</p>
            <div class="hero-actions"><a class="btn btn-primary" href="#katalog">Lihat Katalog</a><a class="btn btn-secondary" href="#cara">Cara Pesan</a></div>
        </div>
        <div class="hero-device" aria-label="Preview undangan digital">
            <div class="phone"><span class="kicker">The Wedding of</span><span class="phone-title serif">Andi<small>&amp;</small>Sari</span><span class="kicker">[Tanggal Acara]</span><span class="btn btn-primary">Buka Undangan</span></div>
        </div>
    </section>

    <section id="katalog" class="wrap catalog">
        <div class="catalog-heading">
            <div><p class="kicker">Katalog template</p><h2 class="serif">Pilih gaya yang paling Anda</h2></div>
            <nav class="filters" aria-label="Filter kategori template">
                <a class="filter {{ $activeCategory === '' ? 'active' : '' }}" @if ($activeCategory === '') aria-current="page" @endif href="{{ route('catalog.index') }}#katalog">Semua</a>
                @foreach ($categories as $category)
                    <a class="filter {{ $activeCategory === $category->slug ? 'active' : '' }}" @if ($activeCategory === $category->slug) aria-current="page" @endif href="{{ route('catalog.index', ['category' => $category->slug]) }}#katalog">{{ $category->name }}</a>
                @endforeach
            </nav>
        </div>
        <div class="product-grid">
            @forelse ($templates as $template)
                @php
                    $thumbnails = is_array($template->thumbnails) ? array_values($template->thumbnails) : [];
                    $palette = match ($template->category?->slug) {
                        'art-adat' => ['#e6ebe5', '#fbfbf8', '#34493d'],
                        'tema-exclusive' => ['#dad9d6', '#1c1e1d', '#ffffff'],
                        'story-ig' => ['#ebdfd8', '#faf4f0', '#7a4b3a'],
                        default => ['#dde3ea', '#f7f9fb', '#2f4560'],
                    };
                @endphp
                <article class="product">
                    <div class="preview" style="background: {{ $palette[0] }}">
                        <div class="mini-phone" style="background: {{ $palette[1] }}; color: {{ $palette[2] }}">
                            @if (! empty($thumbnails[0]))
                                <img src="{{ $thumbnails[0] }}" alt="Preview {{ $template->name }}" loading="lazy">
                            @else
                                <span class="kicker" style="color: currentColor">The Wedding of</span><span class="mini-title serif">{{ $template->name }}</span><span class="kicker" style="color: currentColor">[Tanggal]</span>
                            @endif
                        </div>
                    </div>
                    <div class="product-meta"><div><h3 class="serif">{{ $template->name }}</h3><p>{{ $template->category?->name ?? 'Undangan digital' }}</p></div><span class="price">@if ($template->hasPromo())<span class="old-price">Rp{{ number_format($template->price, 0, ',', '.') }}</span>@endif Rp{{ number_format($template->effectivePrice(), 0, ',', '.') }}</span></div>
                    <div class="product-actions">
                        @if ($template->demoInvitation)<a class="btn btn-secondary" href="{{ route('template.demo', $template) }}">Lihat Demo</a>@else<span class="btn btn-secondary" aria-disabled="true">Segera Hadir</span>@endif
                        <a class="btn btn-primary" href="{{ route('order.create', $template) }}">Pesan</a>
                    </div>
                </article>
            @empty
                <div class="empty">Belum ada template di kategori ini.</div>
            @endforelse
        </div>
    </section>

    <section id="fitur" class="features">
        <div class="wrap">
            <div class="info-heading"><h2 class="serif" style="margin-top: 0">Semua yang tamu Anda butuhkan, dalam satu halaman.</h2></div>
            <div class="feature-grid">
                <div class="feature"><p class="kicker">01</p><h3>RSVP &amp; ucapan</h3><p>Tamu konfirmasi kehadiran dan menulis ucapan langsung dari undangan.</p></div>
                <div class="feature"><p class="kicker">02</p><h3>Nama tamu personal</h3><p>Satu tautan, nama berbeda untuk setiap tamu lewat parameter ?to=.</p></div>
                <div class="feature"><p class="kicker">03</p><h3>Galeri &amp; musik latar</h3><p>Tampilkan foto pre-wedding dan lagu pilihan Anda.</p></div>
                <div class="feature"><p class="kicker">04</p><h3>Fitur tambahan</h3><p>Amplop digital, peta lokasi, dan lainnya sebagai add-on sesuai kebutuhan.</p></div>
            </div>
        </div>
    </section>

    <section id="cara" class="wrap steps">
        <div class="info-heading"><p class="kicker">Cara pesan</p><h2 class="serif">Tiga langkah, undangan siap dibagikan.</h2></div>
        <div class="step-grid">
            <div class="step"><span class="step-number serif">1</span><h3>Pilih template</h3><p>Lihat demo, lalu pilih desain yang cocok dengan tema pernikahan Anda.</p></div>
            <div class="step"><span class="step-number serif">2</span><h3>Kirim data via WhatsApp</h3><p>Nama mempelai, tanggal, lokasi, dan foto. Kami yang menyusunnya.</p></div>
            <div class="step"><span class="step-number serif">3</span><h3>Bagikan tautan</h3><p>Undangan aktif di alamat seperti /undangan/andi-sari, siap dikirim ke tamu.</p></div>
        </div>
    </section>

    <section class="wrap cta">
        <div class="cta-band">
            <h2 class="serif">Mulai undangan Anda hari ini.</h2>
            <p>Konsultasi gratis, tanpa komitmen. Ceritakan rencana pernikahan Anda.</p>
            <a class="btn btn-light" href="{{ $whatsappUrl }}?text={{ rawurlencode('Halo, saya ingin konsultasi undangan digital.') }}">Chat via WhatsApp</a>
        </div>
    </section>
</main>

<footer id="kontak" class="footer">
    <div class="wrap footer-inner">
        <a class="brand serif" href="{{ route('catalog.index') }}" style="color: var(--ink)">Lensaku <em>Invitation</em></a>
        <span>Lensaku Creative · Jawa Timur &amp; Yogyakarta</span>
        <nav class="footer-nav" aria-label="Navigasi footer"><a href="#katalog">Katalog</a><a href="#fitur">Fitur</a><a href="{{ $whatsappUrl }}">WhatsApp</a></nav>
    </div>
</footer>
</body>
</html>