# ARSITEKTUR MVP — REVISI
## Website Katalog Undangan Digital

*Menggantikan bagian 2 pada `brainstorm-website-undangan-digital.md`. Bagian 1 (riset kompetitor) tetap berlaku sebagai rujukan.*

---

## 0. Keputusan yang Mendasari Revisi Ini

| Topik | Keputusan |
|---|---|
| Monetisasi | Harga flat per template (harga coret + promo). Fitur premium dijual sebagai add-on, bukan paket bertingkat. Tidak ada tabel `plans`; kontrol fitur lewat kolom `features` (JSON) per undangan. |
| Masa aktif | Setelah `expires_at`, tamu melihat halaman "masa aktif berakhir". Klien masih bisa memperpanjang selama grace period. `archived` berarti record disimpan sementara asset sudah dihapus. |
| URL publik | Berbasis path: `/undangan/{slug}`. Bukan subdomain. Slug dibekukan setelah publikasi. |
| Rendering | Server-side Blade. Object `CONFIG` beserta `fill()`/`setAttr()` pada template HTML dibuang. |
| Versi template | Folder berversi per desain. `template_version` pada undangan menunjuk folder view, bukan sekadar penanda. |
| Status | Dua kolom terpisah: `status` (lifecycle publik) dan `asset_state` (lifecycle asset). |

Asumsi default yang dipakai dan dapat diubah tanpa mengubah skema: masa aktif 90 hari setelah tanggal acara, grace period 30 hari, notifikasi peringatan pada H-7 sebelum `asset_delete_at`.

---

## 1. Koreksi terhadap Rencana Sebelumnya

Enam hal berikut berubah, bukan hanya ditambah.

**1.1 Status dipecah menjadi dua kolom.** Rencana lama menaruh `deletion_pending` pada kolom `status` yang sama dengan `active`/`expired`, sehingga undangan yang menunggu penghapusan asset kehilangan status publiknya. Sekarang `status` mengurus apa yang dilihat pengunjung, `asset_state` mengurus keberadaan file. Keduanya berjalan independen: undangan `archived` selalu ber-`asset_state` `deleted`, tetapi undangan `expired` bisa `retained` maupun `deletion_pending`.

**1.2 Versi template menjadi struktur folder.** Menyimpan angka `template_version` tidak membekukan apa pun bila semua undangan me-resolve file Blade yang sama. Perubahan layout kini wajib membuat folder versi baru.

**1.3 Metadata asset punya tabel sendiri.** Rencana lama menyebut penyimpanan metadata asset di satu tempat sekaligus memakai `$invitation->asset_disk`. Sekarang: `invitation_media` menyimpan metadata per file (termasuk `disk`-nya sendiri), sedangkan `invitations.asset_disk` hanya menentukan tujuan upload baru. Cleanup menghapus direktori pada seluruh disk yang benar-benar terpakai, bukan hanya satu.

**1.4 Cleanup command diperbaiki.** `->limit(100)->get()` tanpa loop akan menyisakan tunggakan diam-diam. Diganti `chunkById` sampai habis, dengan `--dry-run`, `--limit`, logging jumlah, guard terhadap `expires_at` di masa depan, dan test wajib. Command ini menghapus direktori pelanggan yang sudah membayar; itu satu-satunya alasan ia diperlakukan berbeda dari command lain.

**1.5 Layer katalog dan pemesanan masuk skema.** Riset mendeskripsikannya, rencana lama tidak memodelkannya, padahal langkah terakhir mensyaratkannya sudah tervalidasi.

**1.6 `user_id` dan `guest_id` ada sejak migration pertama.** Keduanya nullable dan belum dipakai di MVP. Menambahkannya nanti berarti migration plus backfill plus penulisan ulang policy.

---

## 2. Skema Database

Notasi: `?` menandai nullable, `FK` menandai foreign key.

### 2.1 Katalog

**`template_categories`** — Art Adat, Art Non Adat, Tema Exclusive, Story IG

```text
id, name, slug (unique), sort_order, is_active, timestamps
```

**`templates`**

```text
id
name                     -- "Art Jawa Merah 2"
slug (unique)            -- dipakai me-resolve direktori view
template_category_id     FK
current_version           -- versi terbaru untuk undangan baru, default 1
price                     -- harga asli, integer rupiah
promo_price ?             -- harga tampil; null berarti tanpa diskon
badges ?                  JSON  -- ["Gratis Template Story IG"]
thumbnails                JSON  -- 2 mockup layar HP pada kartu produk
supported_features        JSON  -- fitur yang mampu dirender desain ini
demo_invitation_id ?     FK invitations  -- target tombol "Lihat"
description ?
is_active, sort_order, timestamps
```

`price` dan `promo_price` disimpan sebagai integer rupiah, bukan decimal. Tidak ada pecahan sen dan pembulatan float pada harga adalah sumber bug yang tidak perlu.

### 2.2 Undangan

**`invitations`**

```text
id
user_id ?                FK users        -- pemilik, untuk dashboard klien
template_id              FK
template_version         -- dibekukan saat publikasi
slug (unique)
slug_locked_at ?         -- terisi saat publikasi; sesudahnya slug read-only

status                   enum: draft | preview | active | expired | archived
asset_state              enum: retained | deletion_pending | deleted
asset_disk               -- tujuan upload baru, default config('filesystems.default')
features                 JSON  -- {"love_story": true, "rsvpkit": false, ...}

groom_nickname, groom_full_name, groom_child_order ?,
groom_father, groom_mother, groom_instagram ?
bride_nickname, bride_full_name, bride_child_order ?,
bride_father, bride_mother, bride_instagram ?

event_date               date   -- tanggal utama; untuk cover, sorting admin, hitung expires_at
quote_text ?, quote_source ?    -- kutipan religi, disesuaikan agama pasangan
opening_words ?, closing_words ?
gift_address ?                  -- alamat pengiriman kado fisik
moderate_wishes          bool, default true

published_at ?
expires_at ?
grace_days               default 30
asset_delete_at ?
expiry_notified_at ?            -- mencegah notifikasi berulang
meta ?                   JSON   -- override OG title/description
timestamps, softDeletes
```

Index: `unique(slug)`, `(status, asset_state, asset_delete_at)`, `(template_id)`, `(user_id)`, `(event_date)`.

Data mempelai ditulis sebagai kolom datar berprefiks, bukan tabel relasi maupun JSON. Jumlahnya selalu tepat dua, validasinya jelas, dan form Filament-nya paling sederhana. Kalau suatu saat butuh lebih dari dua pihak, itu perubahan produk yang memang layak migration sendiri.

**`invitation_events`** — Akad Nikah, Resepsi, dan seterusnya

```text
id, invitation_id FK
title                    -- "Akad Nikah"
starts_at, ends_at ?
timezone                 default "Asia/Jakarta"
venue_name, address, maps_url ?, notes ?
is_primary               bool  -- target countdown dan Save The Date
sort_order, timestamps
```

`timezone` bukan hiasan. Indonesia punya tiga zona waktu, dan countdown maupun file kalender yang salah satu jam akan terlihat langsung oleh ratusan tamu.

**`invitation_stories`** — linimasa cerita cinta

```text
id, invitation_id FK, title, happened_on ?, body,
media_id ? FK invitation_media, sort_order, timestamps
```

**`invitation_bank_accounts`** — amplop digital

```text
id, invitation_id FK, bank_name,
account_number   -- string, bukan integer: nol di depan harus utuh
account_holder, logo_media_id ? FK, sort_order, timestamps
```

**`invitation_media`**

```text
id, invitation_id FK
collection   enum: cover | groom_photo | bride_photo | gallery
                 | story | music | bank_logo | og_image
disk         -- disk file ini, terpisah dari invitations.asset_disk
path, mime, size
width ?, height ?, duration_seconds ?
variants ?   JSON  -- path derivative per ukuran: {"480": "...", "960": "..."}
sort_order, timestamps
```

Index: `(invitation_id, collection, sort_order)`.

Menyimpan `disk` per baris membuat asset lama yang diunggah saat `FILESYSTEM_DISK=local` tetap dapat di-resolve setelah default berpindah ke `r2`. Tanpa itu, peralihan development ke production mematikan seluruh URL asset yang sudah ada.

**`invitation_gift_confirmations`** — form konfirmasi transfer

```text
id, invitation_id FK, sender_name, amount ?,
invitation_bank_account_id ? FK, note ?, proof_path ?,
confirmed_at ?, timestamps
```

Ada pada alur kompetitor (bagian 1.2 nomor 9) tetapi tidak pernah dimodelkan di rencana lama.

### 2.3 Tamu dan Interaksi

**`guests`** — kosong di MVP, terisi ketika add-on RSVPKIT dijual

```text
id, invitation_id FK, name, phone ?, token ? (unique),
group_label ?, quota default 1, opened_at ?, timestamps
```

**`rsvps`**

```text
id, invitation_id FK, guest_id ? FK, name,
attendance   enum: attending | not_attending | tentative
party_size   default 1
message ?, ip_hash ?, user_agent ?, timestamps
```

**`wishes`** — ucapan dan doa

```text
id, invitation_id FK, guest_id ? FK, name, message,
is_approved  bool, ip_hash ?, timestamps
```

Index: `(invitation_id, is_approved, created_at)`.

### 2.4 Pemesanan

**`orders`**

```text
id, order_number (unique)
user_id ? FK, invitation_id ? FK, template_id FK
customer_name, customer_phone, customer_email ?
base_price, addons JSON, discount, total
status          enum: pending | waiting_payment | paid | in_progress
                    | delivered | cancelled | refunded
payment_channel  -- manual_transfer | midtrans
payment_ref ?, paid_at ?, proof_path ?, notes ?
timestamps
```

`addons` menyimpan fitur berbayar yang dibeli beserta harganya saat transaksi. Nilai inilah yang menurunkan `invitations.features`, sehingga perubahan harga add-on di kemudian hari tidak mengubah hak fitur pelanggan lama.

**`invitation_extensions`** — jejak perpanjangan masa aktif

```text
id, invitation_id FK, order_id ? FK,
previous_expires_at, new_expires_at, days, timestamps
```

Kecil, tetapi menyangkut uang yang masuk dan umur file pelanggan. Riwayatnya perlu bisa dibaca ulang.

---

## 3. Versi Template

```text
resources/views/invitations/
├── _shared/
│   └── partials/{countdown,rsvp-form,wishes-list}.blade.php
├── elegant-botanical/
│   ├── v1/
│   │   ├── index.blade.php
│   │   └── sections/{cover,intro,couple,countdown,events,
│   │                 story,gallery,gift,rsvp,wishes,closing}.blade.php
│   └── v2/
└── art-jawa-merah/
    └── v1/
```

Resolve view:

```php
view("invitations.{$invitation->template->slug}.v{$invitation->template_version}.index", [
    'invitation' => $invitation,
    'guestName'  => $guestName,
]);
```

Aturannya:

- Folder versi yang sudah dipakai undangan berstatus `active` bersifat read-only, kecuali perbaikan bug yang tidak mengubah layout.
- Perubahan layout berarti folder versi baru dan `templates.current_version` naik. Undangan lama tetap di versi lamanya.
- Undangan baru memakai `current_version` saat dibuat.
- Template boleh diganti selama `published_at` masih null; sesudah itu penggantian adalah pekerjaan manual yang disengaja, bukan efek samping.

Konsekuensi yang perlu diterima: jumlah folder bertambah seiring waktu, dan perbaikan yang seharusnya berlaku untuk semua versi harus ditaruh di `_shared/`. Ini harga dari janji bahwa undangan pelanggan tidak berubah sendiri.

---

## 4. State Machine

### 4.1 Arti Setiap Status

| Status | Yang dilihat pengunjung | Terima RSVP & ucapan |
|---|---|---|
| `draft` | 404, kecuali dibuka dengan signed preview link | tidak |
| `preview` | undangan penuh dengan data demo | **tidak** |
| `active` | undangan penuh | ya |
| `expired` | halaman "masa aktif berakhir" + CTA perpanjang | tidak |
| `archived` | halaman penutup ringkas, tanpa foto | tidak |

Status `preview` yang menolak tulisan itu penting: demo katalog dibuka banyak orang asing, dan tanpa penolakan itu daftar ucapan pada demo akan penuh sampah dalam hitungan hari.

### 4.2 Transisi

```text
draft ──publish──> active ──expires_at──> expired ──asset_delete_at──> archived
  │                   ▲                      │
  └──> preview        └──── perpanjang ──────┘
```

Dijalankan Scheduler harian:

1. `status=active` dan `expires_at <= now()` → `status=expired`, `asset_delete_at = expires_at + grace_days`, kirim notifikasi ke pemilik.
2. `status=expired`, `asset_delete_at <= now()+7 hari`, `expiry_notified_at` masih null → peringatan terakhir, isi `expiry_notified_at`.
3. `status=expired` dan `asset_delete_at <= now()` → `asset_state=deletion_pending`.
4. Command cleanup: `asset_state=deletion_pending` → hapus direktori pada setiap disk terpakai → `asset_state=deleted`, `status=archived`.

Perpanjangan (dipicu order berstatus `paid`) memajukan `expires_at` dan `asset_delete_at`, mengembalikan `status=active` dan `asset_state=retained`, serta mengosongkan `expiry_notified_at`. Perpanjangan ditolak bila `asset_state` sudah `deleted` — file sudah tidak ada dan tidak ada yang bisa dihidupkan kembali. Penolakan ini harus jelas di UI sebelum pembayaran, bukan sesudah.

Notifikasi sebelum penghapusan bukan fitur tambahan. Yang dihapus adalah foto pernikahan orang, dan kehilangan tanpa peringatan adalah kerusakan yang tidak bisa ditebus dengan refund.

### 4.3 Command Cleanup

```php
php artisan invitations:cleanup-assets [--dry-run] [--limit=]
```

Syarat yang harus dipenuhi:

- `chunkById` sampai habis, bukan `limit()` sekali jalan.
- `--dry-run` mencetak apa yang akan dihapus tanpa menghapus.
- Guard: lewati bila `expires_at` masih di masa depan atau `asset_delete_at` masih null, walaupun `asset_state` sudah `deletion_pending`.
- Hapus direktori pada seluruh `disk` yang tercatat di `invitation_media` milik undangan itu, digabung dengan `invitations.asset_disk`.
- Satu transaksi per undangan; status hanya berubah setelah penghapusan sukses.
- Log jumlah diproses, dilewati, dan gagal.
- Punya test. Termasuk test bahwa undangan `active` tidak pernah tersentuh.

---

## 5. Rendering dan Halaman Publik

### 5.1 Server-Side, Bukan `CONFIG`

Template HTML saat ini merender seluruh isi dari object `CONFIG` di sisi browser melalui `innerHTML`. Pada versi Blade, seluruh `fill()` dan `setAttr()` dihapus dan nilainya dicetak langsung di markup. JavaScript hanya menyisakan perilaku: countdown, animasi reveal, audio, tombol salin nomor rekening, serta submit RSVP dan ucapan.

Alasannya bukan kerapian. Link undangan hidup dari sebaran di WhatsApp, dan preview link WhatsApp tidak menjalankan JavaScript. Dengan render client-side, tautan yang di-share tampil tanpa judul, tanpa nama pasangan, tanpa gambar. Tambahan lain: tidak ada kedipan halaman kosong saat pemuatan, dan nama tamu dari query string tidak lagi lewat `innerHTML`.

### 5.2 Route Publik

```text
GET /undangan/{slug}          -- undangan, hormati status
GET /undangan/{slug}/ics      -- file Save The Date
POST /undangan/{slug}/rsvp    -- throttle
POST /undangan/{slug}/wishes  -- throttle
GET /                          -- katalog
GET /demo/{template:slug}      -- redirect ke demo_invitation
```

Nama tamu dari `?to=` diperlakukan sebagai input tidak dipercaya: strip tag, normalisasi spasi, batasi 60 karakter, escape saat dicetak. Nilai ini juga masuk ke OG title sehingga setiap tamu melihat namanya sendiri pada preview WhatsApp.

Meta OG per undangan (title, description, image dari collection `og_image`) wajib ada sejak langkah pertama, bukan disisipkan belakangan.

### 5.3 Slug

Unik, dibekukan pada publikasi lewat `slug_locked_at`. Dua pasangan bernama Andi dan Sari itu wajar terjadi, jadi generator slug harus menambahkan pembeda. Setelah link tersebar ke ratusan tamu, slug tidak bisa ditarik kembali — perubahan slug pasca-publikasi hanya boleh lewat tindakan admin eksplisit yang sekaligus memasang redirect dari slug lama.

---

## 6. Asset

### 6.1 Struktur Object

```text
invitations/{invitation_id}/
├── cover/{hash}.webp
├── gallery/{hash}.webp
├── story/{hash}.webp
├── og.jpg
└── music/{hash}.mp3
```

Nama berbasis hash membuat file bersifat immutable sehingga aman di-cache selamanya. Prefiks tetap `invitations/{id}/` agar penghapusan cukup satu operasi direktori.

### 6.2 Pipeline

Rencana lama menampilkan `cover.webp` di struktur R2 tetapi tidak menyebut siapa yang mengubahnya menjadi WebP. Alurnya:

```text
Admin memilih file, menekan Simpan
        ↓
Validasi: mime asli via finfo (bukan ekstensi), ukuran maks, dimensi min
        ↓
Simpan original ke disk final
        ↓
Queued job: konversi WebP + derivative 480 / 960 / 1440
        ↓
invitation_media.variants terisi, original dihapus bila tidak diperlukan
```

R2 adalah object storage tanpa transformasi gambar bawaan. Tanpa pipeline ini, foto 4 MB langsung dari HP klien dikirim apa adanya ke tamu yang membuka undangan lewat data seluler di dalam gedung. Itu masalah produk, bukan optimasi.

Musik: satu file, batas ukuran 5 MB, durasi divalidasi. Autoplay dengan suara diblokir browser, jadi pemutaran wajib dipicu oleh tap tombol "Buka Undangan" pada cover — gesture itu satu-satunya kesempatan membuka izin audio.

### 6.3 Disk dan URL

Development memakai `local`. Production memakai `r2` dengan custom domain publik (`AWS_URL=https://cdn.domain.com`) dan `Cache-Control` panjang. Keputusan custom domain ini harus diambil sebelum kode upload ditulis: tanpanya `Storage::url()` pada R2 tidak menghasilkan URL yang bisa dibuka publik, dan seluruh path yang sudah tersimpan harus ditulis ulang.

R2 Lifecycle tetap opsional dan hanya sebagai pengaman prefiks `tmp/`. Alasan pada rencana lama benar: setiap undangan punya `asset_delete_at` sendiri dan itu tidak bisa dinyatakan sebagai aturan lifecycle.

Folder `tmp/` belum diperlukan di MVP. File berpindah dari browser ke lokasi final dalam satu kali submit.

---

## 7. Endpoint Tulis Publik

RSVP dan ucapan adalah dua form terbuka tanpa autentikasi pada halaman yang dibagikan ke ratusan orang. Perlindungan minimum:

- Rate limit per IP dan per undangan pada kedua route.
- Honeypot field plus batas waktu submit minimum.
- `wishes.is_approved` dengan default mengikuti `invitations.moderate_wishes`, dan antrean moderasi di Filament.
- Pagination pada daftar ucapan.
- Tolak seluruh tulisan bila `status != active`.
- Simpan `ip_hash`, bukan IP mentah.

---

## 8. Urutan Implementasi

**Langkah 0 — Verifikasi dan titik awal.** Pastikan versi Filament yang dipilih mendukung Laravel 13.17 sebelum stack dikunci. Repo saat ini belum punya satu commit pun; buat commit awal atas skeleton dan template HTML sebelum mulai, agar setiap langkah berikutnya punya pembanding.

**Langkah 1 — Fondasi data.** Migration, model, relasi, factory, dan seeder untuk seluruh tabel di bagian 2. Seeder mengisi kategori, satu template, dan satu undangan demo berstatus `preview`.

**Langkah 2 — Template menjadi Blade.** Konversi `sample-template-01-elegant-botanical.html` ke struktur berversi, server-side, dengan data dari model. Satu template dulu sampai polanya mantap.

**Langkah 3 — Halaman publik.** Route `/undangan/{slug}`, penanganan `?to=`, gate per status, meta OG, halaman expired dan archived, endpoint ICS.

**Langkah 4 — Panel Filament.** Resource Invitation dengan repeater untuk acara, cerita, galeri, dan rekening. Resource Template dan Order. RSVP dan ucapan sebagai daftar baca dengan aksi moderasi.

**Langkah 5 — Filesystem dan gambar.** Konfigurasi `local` dan `r2`, custom domain, job konversi WebP beserta derivative.

**Langkah 6 — Katalog publik.** Landing page, filter kategori, kartu produk dengan harga coret dan promo, tombol Lihat menuju demo, tombol Order menuju WhatsApp, sticky bottom nav.

**Langkah 7 — Lifecycle masa aktif.** Pengisian `expires_at` dan `asset_delete_at`, state machine, notifikasi, command cleanup lengkap dengan `--dry-run` dan test, registrasi Scheduler, verifikasi cron di server.

**Langkah 8 — Setelah alur manual tervalidasi.** Midtrans dan dashboard klien. `user_id` sudah tersedia sejak langkah 1 sehingga tidak ada backfill.

Perubahan dari rencana lama: katalog naik ke langkah 6 dari sekadar prasyarat yang tak terjadwal, dan lifecycle turun ke langkah 7 karena ia butuh data undangan nyata untuk diuji.

---

## 9. Yang Sengaja Ditunda

Bukan lupa, melainkan diputuskan di luar MVP: analitik kunjungan undangan, per-guest link beserta check-in (kolomnya sudah siap di `guests`), template Story Instagram sebagai produk terpisah, ekspor daftar RSVP, multi-bahasa, dan versi cetak. Semuanya dapat ditambahkan tanpa mengubah tabel yang sudah dirancang di sini.


## 10. Catatan Tambahan

Untuk development lokal, gunakan `FILESYSTEM_DISK=local` agar semua asset tersimpan di `storage/app/public`. Jalankan `php artisan storage:link` untuk membuat symlink ke `public/storage`. Untuk production, gunakan `FILESYSTEM_DISK=r2` dengan custom domain yang sudah dikonfigurasi di Cloudflare R2. Pastikan juga environment variable `AWS_URL` mengarah ke domain publik tersebut.