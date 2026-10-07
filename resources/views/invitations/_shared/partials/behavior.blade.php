{{--
  Perilaku, bukan rendering. Seluruh fill()/setAttr() dari template HTML lama
  sudah dibuang: nilainya dicetak Blade di markup. Yang tersisa di sini hanya
  hal-hal yang memang butuh browser.

  Semua fungsi menoleransi elemen yang tidak ada, karena tiap desain template
  boleh memakai sebagian saja dari perilaku ini.
--}}
<script>
(function () {
    'use strict';

    var pad = function (n) { return String(n).padStart(2, '0'); };

    /* ---------------------------------------------------------------
       Countdown — angka awal sudah dicetak server, ini melanjutkannya.
       --------------------------------------------------------------- */
    (function initCountdown() {
        var grid = document.querySelector('[data-countdown]');
        if (!grid) return;

        var iso = grid.getAttribute('data-countdown');
        if (!iso) return;

        var target = new Date(iso).getTime();
        if (isNaN(target)) return;

        var cells = {
            days: grid.querySelector('[data-cd="days"]'),
            hours: grid.querySelector('[data-cd="hours"]'),
            mins: grid.querySelector('[data-cd="mins"]'),
            secs: grid.querySelector('[data-cd="secs"]')
        };

        var tick = function () {
            var diff = Math.max(0, target - Date.now());
            var value = {
                days: Math.floor(diff / 86400000),
                hours: Math.floor(diff % 86400000 / 3600000),
                mins: Math.floor(diff % 3600000 / 60000),
                secs: Math.floor(diff % 60000 / 1000)
            };
            Object.keys(cells).forEach(function (key) {
                if (cells[key]) cells[key].textContent = pad(value[key]);
            });
        };

        tick();
        setInterval(tick, 1000);
    })();

    /* ---------------------------------------------------------------
       Scroll reveal
       --------------------------------------------------------------- */
    var revealed = false;
    function initReveal() {
        if (revealed) return;
        revealed = true;

        var items = document.querySelectorAll('.reveal');
        if (!('IntersectionObserver' in window)) {
            items.forEach(function (el) { el.classList.add('in'); });
            return;
        }

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });

        items.forEach(function (el) { io.observe(el); });
    }

    /* ---------------------------------------------------------------
       Buka Undangan — satu-satunya kesempatan membuka izin audio.
       Autoplay bersuara diblokir browser, jadi pemutaran harus dipicu
       gesture tap pada tombol ini.
       --------------------------------------------------------------- */
    (function initOpen() {
        var openBtn = document.querySelector('[data-open-invitation]');
        var cover = document.querySelector('[data-cover]');
        var main = document.querySelector('[data-main]');

        // Undangan bisa dirender dalam keadaan sudah terbuka: tautan halaman
        // ucapan (?page=2) dan submit tanpa JavaScript keduanya mendarat di
        // dalam undangan, bukan di sampul. Reveal harus dijalankan sekarang —
        // klik "Buka Undangan" tidak akan pernah datang, dan tanpa .in seluruh
        // isi halaman tetap transparan.
        if (main && main.classList.contains('show')) {
            document.body.classList.add('invitation-open');

            // Musik tidak ikut berputar karena tidak ada gesture yang
            // mengizinkannya; tombolnya ditandai berhenti agar jujur.
            var openMusicBtn = document.querySelector('[data-music-toggle]');
            if (openMusicBtn) openMusicBtn.classList.add('paused');

            initReveal();
            return;
        }

        if (!openBtn || !cover || !main) {
            initReveal();
            return;
        }

        openBtn.addEventListener('click', function () {
            cover.style.display = 'none';
            main.classList.add('show');
            document.body.classList.add('invitation-open');

            var nav = document.querySelector('[data-bottom-nav]');
            if (nav) nav.classList.add('show');

            var audio = document.querySelector('[data-bg-audio]');
            var musicBtn = document.querySelector('[data-music-toggle]');
            if (audio && audio.querySelector('source')) {
                if (musicBtn) musicBtn.classList.add('show');
                audio.play().catch(function () {
                    if (musicBtn) musicBtn.classList.add('paused');
                });
            }

            window.scrollTo(0, 0);
            initReveal();
        });
    })();

    /* ---------------------------------------------------------------
       Music toggle
       --------------------------------------------------------------- */
    (function initMusic() {
        var btn = document.querySelector('[data-music-toggle]');
        var audio = document.querySelector('[data-bg-audio]');
        if (!btn || !audio) return;

        btn.addEventListener('click', function () {
            if (audio.paused) {
                audio.play().catch(function () {});
                btn.classList.remove('paused');
            } else {
                audio.pause();
                btn.classList.add('paused');
            }
        });
    })();

    /* ---------------------------------------------------------------
       Salin nomor rekening / alamat
       --------------------------------------------------------------- */
    (function initCopy() {
        function flash(btn, label) {
            var original = btn.getAttribute('data-copy-label') || btn.textContent;
            btn.setAttribute('data-copy-label', original);
            btn.textContent = label;
            btn.classList.add('copied');
            setTimeout(function () {
                btn.textContent = original;
                btn.classList.remove('copied');
            }, 1600);
        }

        function legacyCopy(text) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) { /* diabaikan */ }
            document.body.removeChild(ta);
        }

        document.addEventListener('click', function (event) {
            var btn = event.target.closest('[data-copy]');
            if (!btn) return;

            event.preventDefault();
            var text = btn.getAttribute('data-copy');
            var label = btn.getAttribute('data-copy-success') || 'Tersalin!';

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text)
                    .then(function () { flash(btn, label); })
                    .catch(function () { legacyCopy(text); flash(btn, label); });
            } else {
                legacyCopy(text);
                flash(btn, label);
            }
        });
    })();

    /* ---------------------------------------------------------------
       Lightbox galeri
       --------------------------------------------------------------- */
    (function initLightbox() {
        var box = document.querySelector('[data-lightbox]');
        if (!box) return;

        var img = box.querySelector('[data-lightbox-img]');

        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-lightbox-src]');
            if (trigger && img) {
                img.setAttribute('src', trigger.getAttribute('data-lightbox-src'));
                img.setAttribute('alt', trigger.getAttribute('data-lightbox-alt') || '');
                box.classList.add('open');
                return;
            }

            if (event.target.closest('[data-lightbox-close]') || event.target === box) {
                box.classList.remove('open');
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') box.classList.remove('open');
        });
    })();

    /* ---------------------------------------------------------------
       Submit RSVP dan ucapan
       Nilai dari server ditulis lewat textContent, tidak lewat innerHTML.
       --------------------------------------------------------------- */
    (function initForms() {
        function setMessage(form, text, ok) {
            var box = form.querySelector('[data-form-msg]');
            if (!box) return;
            box.textContent = text;
            box.className = 'form-msg ' + (ok ? 'ok' : 'err');
        }

        function buildWishItem(wish) {
            var item = document.createElement('div');
            item.className = 'wish-item';

            var name = document.createElement('div');
            name.className = 'w-name';
            name.textContent = wish.name;

            var text = document.createElement('div');
            text.className = 'w-text';
            text.textContent = wish.message;

            var time = document.createElement('span');
            time.className = 'w-time';
            time.textContent = wish.time;

            item.appendChild(name);
            item.appendChild(text);
            item.appendChild(time);
            return item;
        }

        document.querySelectorAll('[data-async-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                var submit = form.querySelector('button[type="submit"]');
                if (submit) submit.disabled = true;
                setMessage(form, 'Mengirim...', true);

                fetch(form.getAttribute('action'), {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                    .then(function (response) {
                        return response.json()
                            .catch(function () { return {}; })
                            .then(function (data) { return { status: response.status, data: data }; });
                    })
                    .then(function (result) {
                        var data = result.data || {};

                        if (result.status === 422 && data.errors) {
                            var first = Object.keys(data.errors)[0];
                            setMessage(form, data.errors[first][0], false);
                            return;
                        }

                        if (result.status === 429) {
                            setMessage(form, data.message || 'Terlalu banyak percobaan. Coba lagi beberapa saat lagi.', false);
                            return;
                        }

                        if (!data.ok) {
                            setMessage(form, data.message || 'Gagal mengirim. Coba lagi.', false);
                            return;
                        }

                        setMessage(form, data.message, true);

                        var targetSelector = form.getAttribute('data-prepend-target');
                        if (targetSelector && data.wish) {
                            var list = document.querySelector(targetSelector);
                            if (list) {
                                var empty = list.querySelector('.wish-empty');
                                if (empty) empty.remove();
                                list.insertBefore(buildWishItem(data.wish), list.firstChild);
                            }
                        }

                        if (form.hasAttribute('data-reset-on-success')) {
                            var keep = form.querySelector('input[name="name"]');
                            var keptName = keep ? keep.value : null;
                            form.reset();
                            if (keep && keptName) keep.value = keptName;
                        }
                    })
                    .catch(function () {
                        setMessage(form, 'Jaringan bermasalah. Coba lagi.', false);
                    })
                    .finally(function () {
                        if (submit) submit.disabled = false;
                    });
            });
        });
    })();
})();
</script>
