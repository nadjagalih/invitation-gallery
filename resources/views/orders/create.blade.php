<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pesan {{ $template->name }} - Lensaku Invitation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <style>
        :root { --paper: #f5f4f0; --ink: #1c1e1d; --muted: #5b605d; --line: #dad8d1; --green: #34493d; --white: #fff; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--paper); color: var(--ink); font-family: "DM Sans", sans-serif; line-height: 1.6; }
        a { color: inherit; text-decoration: none; }
        .wrap { width: min(980px, calc(100% - 32px)); margin: 0 auto; }
        .serif { font-family: "Instrument Serif", serif; }
        .header { padding: 24px 0; border-bottom: 1px solid var(--line); }
        .brand { font-size: 25px; }
        .main { display: grid; grid-template-columns: .8fr 1.2fr; gap: 64px; padding: 72px 0 100px; }
        .kicker { margin: 0; color: var(--muted); font-size: 10px; letter-spacing: .18em; text-transform: uppercase; }
        h1 { margin: 16px 0 14px; font-size: clamp(42px, 6vw, 66px); font-weight: 400; line-height: 1; }
        .intro { color: var(--muted); }
        .summary { margin-top: 36px; padding: 22px; border: 1px solid var(--line); border-radius: 18px; background: var(--white); }
        .summary-name { font-size: 24px; }
        .summary-price { margin-top: 8px; font-size: 18px; font-weight: 700; }
        .old-price { margin-right: 7px; color: var(--muted); font-size: 13px; text-decoration: line-through; font-weight: 400; }
        form { padding: 28px; border-radius: 20px; background: var(--white); box-shadow: 0 20px 50px rgba(28, 30, 29, .08); }
        label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 700; }
        input, textarea { width: 100%; border: 1px solid var(--line); border-radius: 10px; padding: 12px 13px; color: var(--ink); font: inherit; }
        input:focus, textarea:focus { border-color: var(--green); outline: 2px solid rgba(52, 73, 61, .12); }
        .field { margin-bottom: 18px; }
        .hint { margin: 5px 0 0; color: var(--muted); font-size: 12px; }
        .error { margin: 5px 0 0; color: #9b3030; font-size: 12px; }
        .submit { width: 100%; margin-top: 6px; border: 0; border-radius: 999px; padding: 14px 20px; background: var(--green); color: var(--white); font: inherit; font-weight: 700; cursor: pointer; }
        .submit:hover { background: #26392e; }
        .fine-print { margin: 14px 0 0; color: var(--muted); font-size: 11px; text-align: center; }
        .honeypot { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
        @media (max-width: 720px) { .main { grid-template-columns: 1fr; gap: 36px; padding-top: 45px; } form { padding: 22px; } }
    </style>
</head>
<body>
<header class="header"><div class="wrap"><a class="brand serif" href="{{ route('catalog.index') }}">Lensaku <em>Invitation</em></a></div></header>
<main class="wrap main">
    <section>
        <p class="kicker">Form pemesanan</p>
        <h1 class="serif">Mulai dari template yang Anda suka.</h1>
        <p class="intro">Isi kontak Anda. Tim Lensaku akan menghubungi untuk konfirmasi data, add-on, dan pembayaran manual.</p>
        <div class="summary">
            <p class="kicker">Template dipilih</p>
            <div class="summary-name serif">{{ $template->name }}</div>
            <div class="summary-price">
                @if ($template->hasPromo())<span class="old-price">Rp{{ number_format($template->price, 0, ',', '.') }}</span>@endif
                Rp{{ number_format($template->effectivePrice(), 0, ',', '.') }}
            </div>
        </div>
    </section>
    <form method="POST" action="{{ route('order.store', $template) }}">
        @csrf
        <div class="honeypot" aria-hidden="true"><label for="website_url">Website</label><input id="website_url" name="website_url" tabindex="-1" autocomplete="off"></div>
        <div class="field"><label for="customer_name">Nama Anda</label><input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required autofocus autocomplete="name">@error('customer_name')<p class="error">{{ $message }}</p>@enderror</div>
        <div class="field"><label for="customer_phone">Nomor WhatsApp</label><input id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" required inputmode="tel" autocomplete="tel" placeholder="62812...">@error('customer_phone')<p class="error">{{ $message }}</p>@enderror</div>
        <div class="field"><label for="customer_email">Email <span style="font-weight: 400">(opsional)</span></label><input id="customer_email" name="customer_email" value="{{ old('customer_email') }}" type="email" autocomplete="email">@error('customer_email')<p class="error">{{ $message }}</p>@enderror</div>
        <div class="field"><label for="notes">Catatan awal <span style="font-weight: 400">(opsional)</span></label><textarea id="notes" name="notes" rows="4" placeholder="Tanggal acara, add-on yang diminati, atau pertanyaan Anda">{{ old('notes') }}</textarea>@error('notes')<p class="error">{{ $message }}</p>@enderror</div>
        <button class="submit" type="submit">Kirim Pesanan</button>
        <p class="fine-print">Belum ada pembayaran otomatis. Kami akan menghubungi Anda untuk langkah berikutnya.</p>
    </form>
</main>
</body>
</html>
