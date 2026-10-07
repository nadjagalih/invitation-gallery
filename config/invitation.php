<?php

/**
 * Asumsi default lifecycle undangan. Nilai-nilai di sini dapat diubah tanpa
 * mengubah skema database — itu sebabnya ia tinggal di config, bukan di kode.
 */
return [

    /*
    |---------------------------------------------------------------------------
    | Masa aktif
    |---------------------------------------------------------------------------
    | active_days : umur undangan dihitung dari tanggal acara.
    | grace_days  : jeda antara expires_at dan asset_delete_at. Selama jeda ini
    |               klien masih bisa memperpanjang dan file masih utuh.
    | warn_days   : peringatan terakhir dikirim H-n sebelum asset_delete_at.
    */
    'active_days' => (int) env('INVITATION_ACTIVE_DAYS', 90),
    'grace_days' => (int) env('INVITATION_GRACE_DAYS', 30),
    'warn_days' => (int) env('INVITATION_WARN_DAYS', 7),

    /*
    |---------------------------------------------------------------------------
    | Nama tamu dari query string ?to=
    |---------------------------------------------------------------------------
    */
    'guest_name_max_length' => 60,
    'guest_name_fallback' => 'Tamu Undangan',

    /*
    |---------------------------------------------------------------------------
    | Upload
    |---------------------------------------------------------------------------
    | Batas ukuran dalam kilobyte, dimensi dalam piksel.
    */
    'upload' => [
        'image_max_kb' => 8192,
        'image_min_width' => 600,
        'image_min_height' => 600,
        'music_max_kb' => 5120,
        'music_max_seconds' => 600,
        'image_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'music_mimes' => ['audio/mpeg', 'audio/mp3'],
    ],

    /*
    |---------------------------------------------------------------------------
    | Derivative gambar
    |---------------------------------------------------------------------------
    | R2 adalah object storage tanpa transformasi gambar bawaan, jadi ukuran
    | turunan dibuat sekali saat upload oleh queued job.
    */
    'image_widths' => [480, 960, 1440],
    'webp_quality' => 82,

    /*
    |---------------------------------------------------------------------------
    | Disk asset publik
    |---------------------------------------------------------------------------
    | Foto undangan harus dapat dibaca tamu. Development memakai disk public;
    | s3 baru dipilih setelah object storage dikonfigurasi.
    */
    'asset_disk' => env('INVITATION_ASSET_DISK', 'public'),

    /*
    |---------------------------------------------------------------------------
    | Halaman publik
    |---------------------------------------------------------------------------
    */
    'wishes_per_page' => 20,
    // Submit yang datang lebih cepat dari ini hampir pasti bot.
    'min_submit_seconds' => 3,
    // Form yang lebih tua dari ini dianggap basi: token CSRF-nya pun sudah lewat.
    'max_form_age_seconds' => 60 * 60 * 12,
    'honeypot_field' => 'website_url',
    'timestamp_field' => 'form_started_at',
    'rsvp_max_party_size' => 10,
    // Durasi yang dipakai file kalender bila acara tidak punya jam selesai.
    'ics_default_duration_hours' => 2,

    /*
    |---------------------------------------------------------------------------
    | Rate limit endpoint tulis publik
    |---------------------------------------------------------------------------
    | RSVP dan ucapan adalah form terbuka tanpa autentikasi pada halaman yang
    | dibagikan ke ratusan orang. Dibatasi per IP dan per undangan sekaligus:
    | satu IP nakal tidak boleh menghabiskan kuota undangan, dan satu undangan
    | yang diserang dari banyak IP tetap punya plafon.
    */
    'rate_limits' => [
        'per_ip_per_minute' => 5,
        'per_invitation_per_minute' => 30,
        'per_ip_per_invitation_per_hour' => 20,
    ],

    /*
    |---------------------------------------------------------------------------
    | Brand
    |---------------------------------------------------------------------------
    */
    'brand' => [
        'name' => env('BRAND_NAME', 'LENSAKU CREATIVE'),
        'whatsapp' => env('BRAND_WHATSAPP', '6281234567890'),
        'tagline' => env('BRAND_TAGLINE', 'Special Web Invitation'),
    ],

];
