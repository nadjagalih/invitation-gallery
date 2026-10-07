# BRAINSTORMING PRODUK
## Website Katalog Undangan Digital

*Disusun untuk LENSAKU CREATIVE — berdasarkan riset galeriundanganofficial.com*

---

## 1. Ringkasan Riset Kompetitor

Situs galeriundanganofficial.com menjual undangan pernikahan digital dalam bentuk website personal per pasangan, dengan model katalog multi-desain. Berikut pola yang teramati langsung dari halaman utama dan salah satu demo undangan ("Art Jawa Merah 2"):

### 1.1 Struktur Landing Page (Katalog)

- Hero singkat: "Special Web Invitation – Kami siap membuat momen bahagia kamu lebih berkesan", dengan CTA Konsultasi (WhatsApp) dan Fitur.
- Filter kategori di atas katalog: Art Adat, Art Non Adat, Tema Exclusive, Story IG — memudahkan calon pembeli langsung loncat ke gaya yang diminati.
- Penjelasan perbedaan RSVP biasa vs RSVPKIT (fitur premium) ditampilkan sebagai pembanding nilai, bukan hanya daftar fitur.
- Setiap kartu produk menampilkan: 2 mockup layar HP (preview desain), nama tema, label "Gratis Template Story IG", harga coret + harga promo, tombol "Lihat" (preview demo) dan "Order".
- Harga anchoring: harga asli dicoret (mis. Rp350.000) berdampingan dengan harga promo (Rp149.000) — taktik diskon yang konsisten di semua kartu.
- Navigasi bawah yang selalu terlihat (sticky): logo brand, ikon konsultasi WhatsApp, ikon fitur.

### 1.2 Struktur Undangan Itu Sendiri (hasil buka demo langsung)

Saat tombol "Lihat" diklik, calon pembeli diarahkan ke sub-domain khusus (contoh: `web.galeriundanganofficial.com/art-jawa-merah-2/?to=Nama+Tamu`) — artinya setiap tamu bisa menerima link personal dengan nama otomatis tampil di undangan. Urutan konten pada undangan:

1. **Cover** — foto pasangan, "The Wedding Of [Nama]", tanggal, nama tamu personal, tombol "Buka Undangan".
2. **Transisi pembuka** — animasi masuk ke halaman utama begitu tombol diklik.
3. **Intro / kutipan** — kalimat pembuka "We Are Getting Married" + ayat suci (kutipan religi, disesuaikan agama pasangan).
4. **Profil mempelai** — foto, nama lengkap, nama orang tua, tautan Instagram, untuk mempelai pria dan wanita.
5. **Countdown** — hitung mundur Hari/Jam/Menit/Detik menuju hari-H, plus tombol "Save The Date" (simpan ke kalender).
6. **Detail acara** — kartu terpisah untuk Akad Nikah dan Resepsi: tanggal, jam, lokasi, tombol Google Maps.
7. **Cerita cinta (love story)** — linimasa bertahap (Awal Bertemu, dst.) dengan foto — tampak sebagai fitur paket menengah/atas.
8. **Galeri foto** — grid foto pasangan.
9. **Amplop digital** — info rekening bank (dengan tombol salin nomor) dan alamat pengiriman kado fisik, lengkap form konfirmasi transfer.
10. **RSVP** — form nama, konfirmasi hadir/tidak hadir, jumlah tamu, tombol kirim.
11. **Ucapan & doa** — kolom komentar publik yang menampilkan nama, pesan, dan waktu kirim.
12. **Penutup** — ucapan terima kasih dan nama pasangan.

> **Insight kunci:** satu kerangka undangan dapat digunakan berulang untuk banyak pasangan. Desain disimpan sebagai template Blade, sedangkan data pasangan disimpan di database dan diberikan ke template saat halaman dibuka.

---

## 2. Kesimpulan Diskusi: Arsitektur MVP

### 2.1 Stack dan Panel Admin

MVP menggunakan Laravel sebagai backend dan Filament sebagai panel admin. Panel admin mengelola template, data pasangan, acara, galeri, rekening, musik, status undangan, RSVP, dan ucapan.

Template undangan tidak digandakan menjadi file HTML baru untuk setiap pasangan. Setiap desain dibuat sebagai Blade view tetap, sedangkan data yang berubah disimpan di database dan dikirim ke template saat halaman dibuka.

Struktur template yang disarankan:

```text
resources/views/invitations/
├── elegant-botanical.blade.php
├── art-jawa-merah.blade.php
└── minimal-modern.blade.php
```

Template HTML yang tersedia menjadi referensi visual dan struktur section undangan. Pada versi Laravel, data yang tampil berasal dari model dan relasi database, bukan dari konfigurasi yang ditulis ulang untuk setiap pasangan.

### 2.2 Aturan Template dan Undangan

- Satu undangan memiliki satu template aktif.
- Template boleh diganti selama undangan belum dipublikasikan.
- Setelah aktif, perubahan layout template tidak boleh otomatis mengubah undangan pelanggan.
- Setiap undangan menyimpan `template_version` agar versi desain yang digunakan dapat dilacak.
- URL publik menggunakan slug stabil, misalnya `/undangan/andi-sari`.
- Nama tamu personal dapat dikirim melalui parameter `?to=Nama+Tamu`.
- Status publik undangan: `draft`, `preview`, `active`, `expired`, dan `archived`.
- Status internal penghapusan asset: `deletion_pending` dan `deleted`.

Data utama yang disimpan dalam database meliputi identitas pasangan, tanggal acara, detail acara, galeri, cerita cinta, rekening, RSVP, ucapan, slug, status, dan masa aktif undangan.

### 2.3 Rencana Penyimpanan Asset dengan Cloudflare R2

Asset berukuran besar seperti foto dan musik disimpan di Cloudflare R2 agar tidak membebani storage dan bandwidth server Laravel. Database Laravel hanya menyimpan metadata asset, seperti disk, path, tipe file, dan ukuran.

Laravel Filesystem digunakan sebagai abstraction agar kode upload tidak bergantung langsung pada penyimpanan lokal atau R2. Kode aplikasi tetap menggunakan API yang sama:

```php
$path = $request->file('cover')->store(
    "invitations/{$invitation->id}",
    config('filesystems.default')
);
```

Pada development, `FILESYSTEM_DISK` dapat diarahkan ke `local`. Pada production, disk diarahkan ke `r2`. Laravel tidak menyimpan salinan lokal secara otomatis ketika menggunakan R2.

Struktur object di R2 menggunakan ID undangan sebagai prefix:

```text
invitations/{invitation_id}/
├── cover.webp
├── gallery/01.webp
└── music.mp3
```

`{invitation_id}` diisi otomatis dari ID record undangan, bukan ditulis manual. Contoh `invitations/123/cover.webp` berarti asset cover untuk undangan dengan ID `123`.

### 2.4 Alur Upload Asset MVP

Untuk MVP, file tidak perlu masuk ke folder `tmp/` R2. Ketika admin memilih file, file masih berada di browser. Setelah tombol **Simpan** ditekan, file dikirim ke Laravel, divalidasi, lalu disimpan langsung ke lokasi final di R2.

```text
Admin memilih file di browser
	↓
Admin menekan Simpan
	↓
Laravel menerima dan memvalidasi file
	↓
Laravel menyimpan file langsung ke R2
	↓
invitations/{id}/cover.webp
```

Validasi dilakukan sebelum penyimpanan final, misalnya memeriksa bahwa file benar-benar gambar, formatnya sesuai, ukurannya tidak melebihi batas, dan dimensinya memadai.

Folder `tmp/` hanya diperlukan pada tahap lanjutan jika aplikasi menerapkan upload otomatis saat file dipilih, form bertahap, atau proses yang membutuhkan penyimpanan sementara sebelum undangan selesai dibuat.

### 2.5 Masa Aktif dan Penghapusan Asset

Tanggal penghapusan asset disimpan di database melalui field `asset_delete_at`. Field ini merupakan aturan bisnis Laravel, bukan konfigurasi yang dibaca langsung oleh Cloudflare R2.

Contoh alur:

```text
Undangan aktif sampai expires_at
	↓
Undangan berubah menjadi expired
	↓
Masa grace period berakhir
	↓
asset_delete_at tercapai
	↓
Laravel Scheduler menjalankan cleanup command
	↓
Laravel mengirim perintah hapus ke R2
	↓
invitations/{id}/ dihapus
```

Penghapusan otomatis sebaiknya dijalankan melalui Artisan command dan Laravel Scheduler, bukan controller. Scheduler memeriksa undangan yang memenuhi kondisi berikut:

```php
$invitations = Invitation::query()
    ->where('status', 'deletion_pending')
    ->where('asset_delete_at', '<=', now())
    ->limit(100)
    ->get();

foreach ($invitations as $invitation) {
    Storage::disk($invitation->asset_disk)
		->deleteDirectory("invitations/{$invitation->id}");

    $invitation->update([
		'status' => 'deleted',
    ]);
}
```

Server harus memiliki cron atau proses scheduler Laravel yang aktif. R2 tidak mengetahui isi database Laravel dan tidak dapat menentukan apakah asset masih digunakan berdasarkan record database.

R2 Lifecycle bersifat opsional dan hanya disarankan sebagai pengaman untuk object sementara, misalnya prefix `tmp/`. Lifecycle dapat menghapus object berdasarkan prefix dan umur file, tetapi tidak cocok untuk menentukan masa aktif setiap undangan karena setiap undangan dapat memiliki `asset_delete_at` yang berbeda.

### 2.6 Keputusan MVP

Arsitektur MVP yang disepakati:

> **Laravel + Filament sebagai aplikasi pengelola, Blade template tetap sebagai mesin tampilan, database sebagai sumber data undangan, Cloudflare R2 sebagai penyimpanan asset production, dan Laravel Scheduler sebagai pengelola penghapusan asset berdasarkan masa aktif.**

Urutan implementasi yang disarankan:

1. Ubah template HTML saat ini menjadi Blade view dengan data dinamis.
2. Buat model dan migration untuk template, undangan, acara, galeri, rekening, RSVP, dan ucapan.
3. Buat panel Filament untuk mengelola data undangan dan upload asset.
4. Konfigurasikan Laravel Filesystem untuk local saat development dan R2 saat production.
5. Buat URL publik berbasis slug dengan dukungan nama tamu personal.
6. Tambahkan `expires_at`, `asset_delete_at`, status undangan, Artisan cleanup command, dan Laravel Scheduler.
7. Tambahkan checkout Midtrans dan dashboard klien setelah alur katalog serta pemesanan manual tervalidasi.
