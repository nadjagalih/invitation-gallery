{{--
  Kerangka HTML bersama seluruh template undangan. Yang berbeda antar desain
  hanya style dan urutan section; struktur head, meta OG, dan pemuatan perilaku
  JavaScript sama untuk semuanya.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" content="@yield('theme-color', '#8c5b4b')">

@include('invitations._shared.meta', ['meta' => $meta])

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
@yield('fonts')

@yield('styles')
</head>
<body>

@yield('body')

@include('invitations._shared.partials.behavior')
@yield('scripts')

</body>
</html>
