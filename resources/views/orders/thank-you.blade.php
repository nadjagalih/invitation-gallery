<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pesanan Diterima - Lensaku Invitation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <style>
        :root { --paper: #f5f4f0; --ink: #1c1e1d; --muted: #5b605d; --green: #34493d; --white: #fff; }
        * { box-sizing: border-box; }
        body { display: grid; min-height: 100vh; margin: 0; place-items: center; background: var(--paper); color: var(--ink); font-family: "DM Sans", sans-serif; text-align: center; }
        .card { width: min(560px, calc(100% - 32px)); padding: 48px 28px; border-radius: 22px; background: var(--white); box-shadow: 0 20px 50px rgba(28, 30, 29, .08); }
        .kicker { color: var(--muted); font-size: 10px; letter-spacing: .18em; text-transform: uppercase; }
        h1 { margin: 16px 0; font: 400 clamp(42px, 8vw, 64px)/1 "Instrument Serif", serif; }
        p { color: var(--muted); line-height: 1.7; }
        .number { display: inline-block; margin: 12px 0; padding: 10px 14px; border-radius: 8px; background: var(--paper); font-family: monospace; font-weight: 700; }
        .button { display: inline-flex; min-height: 44px; align-items: center; padding: 0 22px; border-radius: 999px; background: var(--green); color: var(--white); font-weight: 700; text-decoration: none; }
    </style>
</head>
<body>
<main class="card">
    <div class="kicker">Pesanan diterima</div>
    <h1>Terima kasih.</h1>
    <p>Pesanan untuk template <strong>{{ $order->template->name }}</strong> sudah tercatat. Simpan nomor order ini untuk percakapan berikutnya.</p>
    <div class="number">{{ $order->order_number }}</div>
    <p>Tim Lensaku akan menghubungi Anda melalui WhatsApp untuk konfirmasi detail dan pembayaran manual.</p>
    <a class="button" href="{{ route('catalog.index') }}">Kembali ke katalog</a>
</main>
</body>
</html>
