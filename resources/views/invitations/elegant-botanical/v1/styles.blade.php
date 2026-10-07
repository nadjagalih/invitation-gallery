{{--
  Style dibekukan bersama versi ini. Sengaja inline, bukan file CSS terpisah:
  undangan adalah satu halaman yang dibuka sekali dari data seluler, jadi satu
  round trip lebih mahal daripada beberapa kilobyte tambahan di HTML.

  Foto cover masuk lewat custom property --cover-image dari markup, supaya file
  ini tetap statis dan bisa di-cache view compiler tanpa data pasangan.
--}}
<style>
  :root{
    --ink:        #3a2e28;
    --ink-soft:   #6b5850;
    --primary:    #8c5b4b;
    --primary-dk: #6b4038;
    --gold:       #b3893f;
    --cream:      #faf3ea;
    --cream-2:    #f2e6d8;
    --white:      #fffdf9;
    --line:       #e3d3c1;

    --font-display: 'Cormorant Garamond', serif;
    --font-script:  'Great Vibes', cursive;
    --font-body:    'Poppins', sans-serif;
  }

  *{ box-sizing:border-box; margin:0; padding:0; }
  html{ scroll-behavior:smooth; }
  body{
    font-family: var(--font-body);
    color: var(--ink);
    background: linear-gradient(180deg,#e9ddce,#cdb89f 60%,#b89b7a);
    -webkit-font-smoothing:antialiased;
  }
  img{ max-width:100%; display:block; }
  button{ font-family:inherit; cursor:pointer; border:none; background:none; }
  a{ color:inherit; text-decoration:none; }

  #frame{
    max-width:480px;
    margin:0 auto;
    background: var(--cream);
    position:relative;
    overflow:hidden;
    min-height:100vh;
    box-shadow: 0 0 60px rgba(0,0,0,.35);
  }

  /* ===================== COVER ===================== */
  #cover{
    position:relative;
    min-height:100vh;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:flex-end;
    text-align:center;
    padding:48px 28px 56px;
    background-image:
      radial-gradient(circle at 50% 20%, rgba(255,255,255,.18), transparent 55%),
      linear-gradient(180deg, rgba(58,46,40,.35), rgba(58,46,40,.65)),
      var(--cover-image, linear-gradient(160deg,#8c5b4b,#6b4038));
    background-size:cover;
    background-position:center;
    color:#fff;
    overflow:hidden;
  }
  #cover .eyebrow{
    letter-spacing:4px;
    font-size:11px;
    font-weight:500;
    text-transform:uppercase;
    opacity:.85;
    margin-bottom:10px;
  }
  #cover .names{
    font-family:var(--font-script);
    font-size:64px;
    line-height:1;
    margin-bottom:6px;
    text-shadow:0 4px 18px rgba(0,0,0,.35);
  }
  #cover .date{
    font-size:14px;
    letter-spacing:2px;
    margin-bottom:34px;
    opacity:.9;
  }
  #cover .guest-box{
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.4);
    border-radius:14px;
    padding:18px 22px;
    width:100%;
    backdrop-filter:blur(2px);
    margin-bottom:22px;
  }
  #cover .guest-box small{
    display:block;
    font-size:11px;
    letter-spacing:1px;
    text-transform:uppercase;
    opacity:.8;
    margin-bottom:6px;
  }
  #cover .guest-box .guest-name{
    font-family:var(--font-display);
    font-size:22px;
    font-weight:600;
  }
  #open-btn{
    display:inline-flex;
    align-items:center;
    gap:10px;
    background:var(--white);
    color:var(--primary-dk);
    padding:14px 30px;
    border-radius:999px;
    font-size:14px;
    font-weight:600;
    letter-spacing:.5px;
    box-shadow:0 10px 30px rgba(0,0,0,.25);
    animation:pulse 2.4s ease-in-out infinite;
  }
  @keyframes pulse{
    0%,100%{ transform:scale(1); }
    50%{ transform:scale(1.045); }
  }

  /* ===================== UTILITAS ===================== */
  #main{ display:none; position:relative; }
  #main.show{ display:block; animation:fadeIn .6s ease; }
  @keyframes fadeIn{ from{opacity:0} to{opacity:1} }

  section{
    padding:64px 30px;
    position:relative;
    text-align:center;
  }
  .reveal{ opacity:0; transform:translateY(24px); transition:opacity .8s ease, transform .8s ease; }
  .reveal.in{ opacity:1; transform:translateY(0); }

  .kicker{
    font-family:var(--font-script);
    font-size:34px;
    color:var(--primary);
    margin-bottom:2px;
  }
  h2.section-title{
    font-family:var(--font-display);
    font-size:30px;
    font-weight:600;
    letter-spacing:.5px;
    margin-bottom:18px;
  }
  .divider{
    width:70px; height:2px;
    background:var(--gold);
    margin:18px auto;
    position:relative;
  }
  .divider::before{
    content:'\2740';
    position:absolute;
    left:50%; top:50%;
    transform:translate(-50%,-50%);
    background:var(--cream);
    color:var(--gold);
    padding:0 10px;
    font-size:14px;
  }
  .muted{ color:var(--ink-soft); font-size:14.5px; line-height:1.9; }

  /* ===================== INTRO ===================== */
  #intro{ background:var(--cream); padding-top:70px; }
  #intro .quote-box{
    border-top:1px solid var(--line);
    border-bottom:1px solid var(--line);
    padding:26px 6px;
    margin-top:26px;
  }
  #intro .quote-text{
    font-family:var(--font-display);
    font-style:italic;
    font-size:17px;
    line-height:1.85;
    color:var(--ink);
  }
  #intro .quote-source{
    display:block;
    margin-top:14px;
    font-size:12px;
    letter-spacing:1.5px;
    text-transform:uppercase;
    color:var(--primary);
  }

  /* ===================== COUPLE ===================== */
  #couple{ background:var(--cream-2); }
  .couple-wrap{
    display:flex;
    flex-direction:column;
    gap:34px;
    margin-top:20px;
  }
  .person-photo{
    width:150px; height:150px;
    border-radius:50%;
    margin:0 auto 18px;
    background:linear-gradient(145deg,var(--primary),var(--gold));
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-family:var(--font-script); font-size:30px;
    border:6px solid var(--white);
    box-shadow:0 10px 26px rgba(140,91,75,.35);
    overflow:hidden;
  }
  .person-photo img{ width:100%; height:100%; object-fit:cover; }
  .person-name{ font-family:var(--font-display); font-size:24px; font-weight:600; }
  .person-full{ font-size:13px; color:var(--ink-soft); margin-top:2px; }
  .person-parents{ font-size:13px; color:var(--ink-soft); margin-top:10px; line-height:1.7; max-width:280px; margin-left:auto; margin-right:auto; }
  .ig-link{
    display:inline-flex; align-items:center; gap:6px;
    margin-top:12px;
    font-size:12px; font-weight:600; letter-spacing:.5px;
    color:var(--primary-dk);
    border:1px solid var(--primary-dk);
    padding:7px 16px;
    border-radius:999px;
  }
  .amp{
    font-family:var(--font-script);
    font-size:30px;
    color:var(--gold);
  }

  /* ===================== COUNTDOWN ===================== */
  #countdown{ background:var(--cream); }
  .cd-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:10px;
    max-width:360px;
    margin:26px auto 8px;
  }
  .cd-cell{
    background:var(--white);
    border:1px solid var(--line);
    border-radius:12px;
    padding:14px 4px;
  }
  .cd-num{ font-family:var(--font-display); font-size:26px; font-weight:600; color:var(--primary-dk); }
  .cd-label{ font-size:10px; letter-spacing:1px; text-transform:uppercase; color:var(--ink-soft); margin-top:2px; }
  .btn{
    display:inline-flex; align-items:center; justify-content:center; gap:8px;
    background:var(--primary-dk);
    color:#fff;
    padding:13px 26px;
    border-radius:999px;
    font-size:13px; font-weight:600; letter-spacing:.4px;
    margin-top:22px;
    box-shadow:0 8px 20px rgba(107,64,56,.3);
  }
  .btn.outline{
    background:transparent;
    border:1.5px solid var(--primary-dk);
    color:var(--primary-dk);
    box-shadow:none;
  }
  .btn-block{ width:100%; }
  .btn[disabled]{ opacity:.6; cursor:progress; }

  /* ===================== EVENTS ===================== */
  #events{
    background:linear-gradient(180deg, var(--primary-dk), var(--primary));
    color:#fff;
  }
  #events .section-title, #events .kicker{ color:#fff; }
  #events .muted{ color:rgba(255,255,255,.8); }
  .event-card{
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.25);
    border-radius:18px;
    padding:28px 22px;
    margin-top:24px;
    backdrop-filter:blur(2px);
  }
  .event-card + .event-card{ margin-top:20px; }
  .event-card .ev-title{ font-family:var(--font-script); font-size:30px; margin-bottom:10px; }
  .event-card .ev-date{ font-size:14px; font-weight:600; letter-spacing:.5px; }
  .event-card .ev-time{ font-size:13px; opacity:.9; margin-top:2px; }
  .event-card .ev-place{ font-size:13.5px; margin-top:14px; line-height:1.7; opacity:.95; }
  .event-card .ev-notes{ font-size:12.5px; margin-top:10px; opacity:.85; font-style:italic; }
  .event-card .btn{
    background:#fff;
    color:var(--primary-dk);
    box-shadow:none;
  }

  /* ===================== LOVE STORY ===================== */
  #story{ background:var(--cream); }
  .story-item{
    text-align:left;
    display:flex;
    gap:16px;
    align-items:flex-start;
    margin-top:26px;
    padding-left:4px;
    border-left:2px solid var(--line);
    padding-bottom:6px;
  }
  .story-item .dot{
    width:12px; height:12px; border-radius:50%;
    background:var(--primary);
    margin-left:-7px; margin-top:6px;
    flex:0 0 auto;
    box-shadow:0 0 0 4px var(--cream);
  }
  .story-item .txt{ padding-left:12px; }
  .story-item .st-title{ font-family:var(--font-display); font-size:19px; font-weight:600; color:var(--primary-dk); }
  .story-item .st-date{ font-size:11px; letter-spacing:1px; text-transform:uppercase; color:var(--gold); margin:2px 0 8px; }
  .story-item .st-text{ font-size:13.5px; line-height:1.8; color:var(--ink-soft); }
  .story-item .st-photo{ border-radius:10px; overflow:hidden; margin-top:10px; }

  /* ===================== GALLERY ===================== */
  #gallery{ background:var(--cream-2); }
  .gal-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:8px;
    margin-top:24px;
  }
  .gal-item{
    aspect-ratio:1/1;
    border-radius:10px;
    overflow:hidden;
    background:linear-gradient(145deg,var(--primary),var(--gold));
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-size:11px; font-weight:600;
    cursor:pointer;
    padding:0;
    width:100%;
  }
  .gal-item img{ width:100%; height:100%; object-fit:cover; }

  #lightbox{
    position:fixed; inset:0; background:rgba(20,14,12,.92);
    display:none; align-items:center; justify-content:center;
    z-index:999; padding:20px;
  }
  #lightbox.open{ display:flex; }
  #lightbox img{ max-width:100%; max-height:80vh; border-radius:8px; }
  #lightbox .close-lb{ position:absolute; top:20px; right:24px; color:#fff; font-size:26px; }

  /* ===================== GIFT ===================== */
  #gift{ background:var(--cream); }
  .bank-card{
    background:var(--white);
    border:1px solid var(--line);
    border-radius:16px;
    padding:22px;
    margin-top:22px;
    text-align:left;
  }
  .bank-card .bank-logo{ height:22px; width:auto; margin-bottom:10px; }
  .bank-card .bank-name{ font-size:13px; font-weight:600; letter-spacing:.5px; color:var(--primary-dk); text-transform:uppercase; }
  .bank-card .bank-number{ font-family:var(--font-display); font-size:24px; font-weight:600; margin:6px 0 2px; letter-spacing:1px; }
  .bank-card .bank-owner{ font-size:13px; color:var(--ink-soft); }
  .copy-btn{
    margin-top:14px;
    font-size:12px; font-weight:600;
    border:1px solid var(--primary-dk);
    color:var(--primary-dk);
    padding:8px 16px;
    border-radius:999px;
    display:inline-flex; align-items:center; gap:6px;
  }
  .copy-btn.copied{ background:var(--primary-dk); color:#fff; }
  .addr-card{
    background:var(--white);
    border:1px solid var(--line);
    border-radius:16px;
    padding:22px;
    margin-top:16px;
    text-align:left;
    font-size:13.5px;
    line-height:1.8;
  }

  /* ===================== RSVP & FORM ===================== */
  #rsvp{ background:var(--cream-2); }
  form{ margin-top:24px; text-align:left; }
  .field{ margin-bottom:14px; }
  .field label{ display:block; font-size:12px; font-weight:600; letter-spacing:.5px; color:var(--ink-soft); margin-bottom:6px; text-transform:uppercase; }
  .field .optional{ text-transform:none; font-weight:400; opacity:.7; }
  .field input[type=text], .field select, .field textarea{
    width:100%;
    padding:12px 14px;
    border:1px solid var(--line);
    border-radius:10px;
    background:var(--white);
    font-family:inherit;
    font-size:14px;
    color:var(--ink);
  }
  .field textarea{ resize:vertical; min-height:80px; }
  .field.has-error input[type=text], .field.has-error select, .field.has-error textarea{ border-color:#c0392b; }
  .field-error{ margin-top:6px; font-size:12px; line-height:1.5; color:#9c3025; }
  .radio-row{ display:flex; gap:10px; }
  .radio-row label{
    flex:1;
    text-align:center;
    border:1px solid var(--line);
    border-radius:10px;
    padding:11px 6px;
    font-size:13px;
    font-weight:500;
    background:var(--white);
    text-transform:none;
  }
  .radio-row input{ display:none; }
  .radio-row input:checked + span{ font-weight:700; }
  .radio-row label:has(input:checked){
    background:var(--primary-dk);
    border-color:var(--primary-dk);
    color:#fff;
  }

  /* Honeypot: tersembunyi dari manusia, tetap terisi oleh bot yang mengisi
     seluruh input. display:none dihindari karena sebagian bot melewatinya. */
  .hp-field{
    position:absolute !important;
    left:-9999px;
    width:1px; height:1px;
    overflow:hidden;
  }

  .form-msg{ margin-top:14px; font-size:13px; display:none; padding:12px; border-radius:10px; line-height:1.6; }
  .form-msg.ok{ display:block; background:#e7f3e8; color:#2e6b34; }
  .form-msg.err{ display:block; background:#fbe9e7; color:#9c3025; }
  .form-notice{
    margin-top:24px;
    background:var(--white);
    border:1px dashed var(--line);
    border-radius:12px;
    padding:16px;
    font-size:13px;
    line-height:1.7;
    color:var(--ink-soft);
  }

  /* ===================== WISHES ===================== */
  #wishes{ background:var(--cream); }
  .wish-form{ margin-bottom:6px; }
  .wish-list{ margin-top:24px; text-align:left; max-height:340px; overflow-y:auto; padding-right:4px; }
  .wish-item{
    background:var(--white);
    border:1px solid var(--line);
    border-radius:12px;
    padding:14px 16px;
    margin-bottom:10px;
  }
  .wish-item .w-name{ font-size:13.5px; font-weight:600; color:var(--primary-dk); }
  .wish-item .w-text{ font-size:13px; color:var(--ink-soft); margin-top:4px; line-height:1.6; white-space:pre-line; }
  .wish-item .w-time{ font-size:10.5px; color:var(--gold); margin-top:6px; display:block; }
  .wish-empty{ font-size:13px; color:var(--ink-soft); padding:8px 0; }
  .wish-pagination{ margin-top:14px; }
  .pager{ display:flex; align-items:center; justify-content:center; gap:12px; font-size:12px; color:var(--ink-soft); }
  .pager a, .pager span.disabled{
    border:1px solid var(--line);
    background:var(--white);
    border-radius:999px;
    padding:7px 14px;
    font-weight:600;
    color:var(--primary-dk);
  }
  .pager span.disabled{ opacity:.45; color:var(--ink-soft); }

  /* ===================== CLOSING ===================== */
  #closing{
    background:var(--primary-dk);
    color:#fff;
    padding-bottom:110px;
    text-align:center;
    padding-left:30px;
    padding-right:30px;
    padding-top:64px;
  }
  #closing .kicker{ color:#fff; }
  #closing p{ font-size:13.5px; line-height:1.9; opacity:.9; max-width:320px; margin:0 auto; }
  #closing .thanks-names{ font-family:var(--font-script); font-size:38px; margin-top:18px; }
  #closing .credit{ margin-top:40px; font-size:11px; opacity:.6; letter-spacing:.5px; }

  /* ===================== FLOATING CONTROLS ===================== */
  #music-btn{
    position:fixed;
    bottom:86px; right:calc(50% - 240px + 18px);
    width:46px; height:46px;
    border-radius:50%;
    background:var(--primary-dk);
    color:#fff;
    display:none;
    align-items:center; justify-content:center;
    font-size:18px;
    box-shadow:0 8px 20px rgba(0,0,0,.3);
    z-index:60;
  }
  #music-btn.show{ display:flex; }
  #music-btn .disc{ animation:spin 3.5s linear infinite; }
  #music-btn.paused .disc{ animation-play-state:paused; }
  @keyframes spin{ from{transform:rotate(0)} to{transform:rotate(360deg)} }

  #bottom-nav{
    position:fixed; left:50%; transform:translateX(-50%);
    bottom:0; width:100%; max-width:480px;
    background:var(--white);
    border-top:1px solid var(--line);
    display:none;
    justify-content:space-around;
    padding:10px 6px 14px;
    z-index:60;
  }
  #bottom-nav.show{ display:flex; }
  #bottom-nav a{
    display:flex; flex-direction:column; align-items:center; gap:4px;
    font-size:10px; color:var(--ink-soft); font-weight:500;
  }
  #bottom-nav a .ic{ font-size:17px; color:var(--primary-dk); }

  @media (min-width:481px){
    #music-btn{ right:calc(50% - 240px + 18px); }
  }
  @media (prefers-reduced-motion:reduce){
    html{ scroll-behavior:auto; }
    .reveal{ opacity:1; transform:none; transition:none; }
    #open-btn, #music-btn .disc{ animation:none; }
  }
</style>
