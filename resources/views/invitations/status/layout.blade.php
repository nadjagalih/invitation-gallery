{{--
  Halaman status: expired dan archived. Sengaja tanpa foto, tanpa nama tamu, dan
  tanpa musik — undangan yang sudah tidak aktif tidak boleh lagi membocorkan isi
  album pernikahan lewat tautan yang sudah tersebar.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#6b4038">

@include('invitations._shared.meta', ['meta' => $meta])

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<style>
  *{ box-sizing:border-box; margin:0; padding:0; }
  body{
    font-family:'Poppins',sans-serif;
    color:#3a2e28;
    background:linear-gradient(180deg,#e9ddce,#cdb89f 60%,#b89b7a);
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:24px;
    -webkit-font-smoothing:antialiased;
  }
  .card{
    max-width:420px;
    width:100%;
    background:#faf3ea;
    border-radius:20px;
    padding:44px 30px;
    text-align:center;
    box-shadow:0 20px 60px rgba(0,0,0,.28);
  }
  .mark{ font-size:34px; margin-bottom:14px; }
  h1{
    font-family:'Cormorant Garamond',serif;
    font-size:27px;
    font-weight:600;
    line-height:1.3;
    margin-bottom:8px;
  }
  .couple{ font-size:13px; letter-spacing:1.5px; text-transform:uppercase; color:#8c5b4b; margin-bottom:20px; }
  p{ font-size:14px; line-height:1.85; color:#6b5850; }
  p + p{ margin-top:12px; }
  .divider{ width:60px; height:2px; background:#b3893f; margin:24px auto; }
  .btn{
    display:inline-flex; align-items:center; justify-content:center; gap:8px;
    background:#6b4038; color:#fff;
    padding:13px 26px; border-radius:999px;
    font-size:13px; font-weight:600;
    margin-top:24px;
    box-shadow:0 8px 20px rgba(107,64,56,.3);
    text-decoration:none;
  }
  .btn.outline{ background:transparent; border:1.5px solid #6b4038; color:#6b4038; box-shadow:none; margin-left:6px; }
  .credit{ margin-top:30px; font-size:11px; color:#6b5850; opacity:.7; letter-spacing:.5px; }
</style>
</head>
<body>
<main class="card">
    @yield('content')
    <div class="credit">{{ config('invitation.brand.name') }} &middot; {{ config('invitation.brand.tagline') }}</div>
</main>
</body>
</html>
