<?php
// Instaweb landing page (redesign). Replaces app/Views/marketing/home.php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$url = static function (string $target) use ($baseUrl): string {
    $target = trim($target);
    if ($target === '') { return ''; }
    if (preg_match('#^(https?:)?//#i', $target) || str_starts_with($target, 'mailto:') || str_starts_with($target, 'tel:') || str_starts_with($target, '#')) { return $target; }
    return $baseUrl . '/' . ltrim($target, '/');
};
$e = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$primaryUrl = $e($url((string) ($settings['homepage_cta_url'] ?? '/register')));
$loginUrl = $e($url((string) ($settings['homepage_secondary_cta_url'] ?? '/login')));
$templatesUrl = '#templates';
?>
<style>
@property --c{syntax:'<color>';inherits:true;initial-value:#38bdf8}
body:has(.nx) .public-header{background:rgba(7,11,20,.55)!important;backdrop-filter:blur(16px) saturate(140%);-webkit-backdrop-filter:blur(16px) saturate(140%);border-bottom:1px solid rgba(255,255,255,.08)!important;box-shadow:none!important}
body:has(.nx) .public-header .nav-link,body:has(.nx) .public-header .header-logo-text,body:has(.nx) .public-header .btn-text{color:#ffffff!important}
.nx{--bg:#08111f;--bg2:#101d33;--tx:#ffffff;--mu:#becae0;--ac:#5eead4;--c:#60a5fa;background:var(--bg);color:var(--tx);font-family:'DM Sans',system-ui,sans-serif;overflow-x:clip;line-height:1.6}
body .nx h1,body .nx h2,body .nx h3{font-family:'Space Grotesk','DM Sans',sans-serif;letter-spacing:-.035em;margin:0;line-height:1.05;color:#ffe200 !important;}
.nx p{margin:0}.nx a{text-decoration:none}
.nx-wrap{max-width:1160px;margin:0 auto;padding:0 24px}
.nx-sec{padding:120px 0;position:relative;border-top:1px solid rgba(190,202,224,.1)}
#audiences{background:#0b1729}.nx #how{background:#081321}.nx #pricing{background:#0d192c}.nx #templates{background:#081321}
.nx-h2{font-size:clamp(2rem,4.5vw,3.4rem);font-weight:700}
.nx-lede{color:var(--mu);font-size:1.1rem;max-width:56ch;margin-top:18px}
.nx em{font-style:normal;color:var(--ac)}
.nx-btn{display:inline-flex;align-items:center;gap:8px;padding:15px 28px;border-radius:999px;font-weight:700;transition:transform .25s,box-shadow .25s,background .25s}
.nx-btn.p{background:var(--ac);color:#04201c;box-shadow:0 10px 40px -10px var(--ac)}
.nx-btn.p:hover{transform:translateY(-3px);box-shadow:0 18px 50px -8px var(--ac)}
.nx-btn.g{border:1px solid rgba(255,255,255,.2);color:var(--tx)}
.nx-btn.g:hover{background:rgba(255,255,255,.08);transform:translateY(-3px)}
.nx-btn:focus-visible,.nx a:focus-visible{outline:3px solid #fff;outline-offset:3px}
/* reveal */
.nx.js [data-r]{opacity:0;transform:translateY(44px);transition:opacity .9s,transform .9s cubic-bezier(.2,.7,.2,1);transition-delay:var(--d,0s)}
.nx.js [data-r=left]{transform:translateX(-70px)}.nx.js [data-r=right]{transform:translateX(70px)}.nx.js [data-r=zoom]{transform:scale(.9)}
.nx.js [data-r].in{opacity:1;transform:none}
/* hero */
.nx-hero{min-height:100svh;display:flex;align-items:center;padding:110px 0 80px;position:relative;background:transparent}
.nx-hero-grid{display:grid;grid-template-columns:1.05fr 1fr;gap:40px;align-items:center}
.nx-hero h1{font-size:clamp(2.7rem,6.4vw,5.2rem);font-weight:700}
.nx-hero .nx-lede{font-size:1.18rem}
.nx-actions{display:flex;flex-wrap:wrap;gap:14px;margin-top:34px}
.nx-note{margin-top:18px;color:var(--mu);font-size:.95rem}
.nx-who{display:flex;flex-wrap:wrap;gap:10px;margin-top:30px}
.nx-who span{border:1px solid rgba(255,255,255,.14);border-radius:999px;padding:6px 14px;font-size:.9rem;color:var(--mu)}
.nx-scene{position:relative;height:540px;perspective:1300px;display:grid;place-items:center}
.nx-rig{transform-style:preserve-3d;transform:translate3d(var(--scroll-x,0px),var(--scroll-y,0px),0) rotateX(calc(var(--rx,0deg) + var(--scroll-rx,0deg))) rotateY(calc(var(--ry,0deg) + var(--scroll-ry,0deg)));transition:transform .18s ease-out;position:relative;width:380px;height:300px;will-change:transform}
.nx-float,.nx-spin{transform-style:preserve-3d;position:absolute;inset:0}
@keyframes nxfloat{50%{transform:translateY(-18px)}}
@keyframes nxspin{from{transform:rotateY(-26deg) rotateX(6deg)}to{transform:rotateY(26deg) rotateX(3deg)}}
.nx-device-stage{position:absolute;inset:0}.nx-laptop{position:absolute;right:-34px;top:42px;width:385px;height:248px;overflow:hidden;border:9px solid #334155;border-radius:16px;background:#08111f;box-shadow:0 30px 64px rgba(0,0,0,.45)}
.nx-laptop:after{content:'';position:absolute;left:-30px;right:-30px;bottom:-24px;height:26px;border-radius:0 0 50% 50%;background:#64748b;box-shadow:0 9px 0 #1e293b}.nx-code-bar{display:flex;gap:6px;align-items:center;height:28px;padding:0 11px;border-bottom:1px solid #26354a;background:#111c2e}.nx-code-bar i{width:7px;height:7px;border-radius:50%;background:#fb7185}.nx-code-bar i:nth-child(2){background:#facc15}.nx-code-bar i:nth-child(3){background:#5eead4}.nx-code-bar span{margin-left:7px;color:#9fb0c8;font:10px 'IBM Plex Mono',monospace}
.nx-code{padding:19px 20px;color:#d9e7ff;font:11px/1.85 'IBM Plex Mono',monospace}.nx-code b{color:#f0abfc;font-weight:500}.nx-code i{color:#5eead4;font-style:normal}.nx-code .indent{padding-left:18px}.nx-code .comment{color:#8192aa}
.nx-phone{position:absolute;left:-4px;top:14px;width:150px;height:286px;padding:11px;border:7px solid #475569;border-radius:26px;background:#0b1322;box-shadow:0 24px 48px rgba(0,0,0,.42);z-index:2}.nx-phone:before{content:'';display:block;width:54px;height:8px;margin:0 auto 10px;border-radius:999px;background:#334155}.nx-phone-ui{height:237px;padding:12px;border-radius:14px;background:#edf7f2;color:#15251e;font:10px 'DM Sans',sans-serif}.nx-phone-ui strong{display:block;font-size:13px}.nx-phone-ui small{display:block;margin-top:3px;color:#607568}.nx-phone-stats{display:grid;grid-template-columns:1fr 1fr;gap:7px;margin-top:13px}.nx-phone-stats span{padding:8px;border-radius:8px;background:#fff;color:#294436;font-weight:700}.nx-phone-stats em{display:block;margin-top:3px;color:#6b7f72;font-size:8px;font-style:normal;font-weight:500}.nx-phone-chart{height:68px;margin-top:10px;padding:10px;border-radius:9px;background:#fff}.nx-phone-chart i{display:inline-block;width:10px;height:var(--h);margin-right:5px;vertical-align:bottom;border-radius:3px 3px 0 0;background:#59b98d}
.nx-device-chip{position:absolute;z-index:3;padding:8px 11px;border:1px solid rgba(190,202,224,.24);border-radius:10px;background:#121f35;color:#e8f1ff;font-size:.76rem;box-shadow:0 10px 28px rgba(0,0,0,.25)}.nx-device-chip.live{right:-5px;bottom:0}.nx-device-chip.sync{left:120px;bottom:18px;color:#a7f3d0}
.nx-cloud{position:absolute;left:50%;top:-70px;width:200px;height:96px;margin-left:-100px;transform:translate3d(var(--cloud-x,0px),var(--cloud-y,0px),70px) scale(var(--cloud-scale,1));will-change:transform}
.nx-cloud:before,.nx-cloud:after,.nx-cloud span{content:'';position:absolute;background:linear-gradient(#f4fffe,#a7f3ec);border-radius:50%}
.nx-cloud span{left:0;bottom:0;width:200px;height:58px;border-radius:40px}
.nx-cloud:before{width:86px;height:86px;left:30px;top:0}.nx-cloud:after{width:104px;height:104px;left:78px;top:-12px}
.nx-cloud{filter:drop-shadow(0 0 34px rgba(45,212,191,.55))}
.nx-cloud svg{position:absolute;left:50%;top:34px;margin-left:-14px;z-index:2;width:28px;height:28px;stroke:#0f766e}
.nx-beam{position:absolute;left:50%;top:44px;width:2px;height:86px;margin-left:-1px;background:repeating-linear-gradient(to top,var(--ac) 0 6px,transparent 6px 14px);background-size:2px 14px;animation:nxbeam .9s linear infinite;transform:translateZ(40px)}
@keyframes nxbeam{to{background-position:0 -14px}}
.nx-chip{position:absolute;padding:8px 14px;border-radius:12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.16);backdrop-filter:blur(8px);font-size:.85rem;animation:nxfloat var(--t,5s) ease-in-out infinite}
.nx-scroll{position:absolute;left:50%;bottom:26px;width:24px;height:40px;margin-left:-12px;border:2px solid rgba(255,255,255,.35);border-radius:14px}
.nx-scroll:after{content:'';position:absolute;left:9px;top:7px;width:4px;height:8px;border-radius:2px;background:var(--ac);animation:nxdot 1.6s infinite}
@keyframes nxdot{60%{transform:translateY(14px);opacity:0}}
/* audience */
.nx-aud{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-top:56px}
.nx-card{background:linear-gradient(160deg,rgba(255,255,255,.1),rgba(255,255,255,.035));border:1px solid rgba(190,202,224,.18);border-radius:20px;padding:28px;transition:transform .3s,border-color .3s}
.nx-card:hover{transform:translateY(-8px);border-color:var(--k)}
.nx-card svg{width:30px;height:30px;stroke:var(--k);margin-bottom:18px}
.nx-card h3{font-size:1.35rem;margin-bottom:10px}.nx-card p{color:var(--mu);font-size:.98rem}
/* features */
.nx-feat{display:grid;grid-template-columns:1fr 1fr;gap:70px;align-items:center;min-height:80vh}
.nx-feat.rev>.nx-vis{order:-1}
.nx-feat ul{list-style:none;padding:0;margin:26px 0 0;display:grid;gap:12px;color:var(--mu)}
.nx-feat li:before{content:'';display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--ac);margin-right:12px}
.nx-vis{position:relative;height:380px}
.nx-vis .bk{position:absolute;inset:8% 4%;border-radius:32px;background:radial-gradient(circle at 30% 30%,var(--k),transparent 70%);opacity:.28;filter:blur(30px)}
.nx-win{position:absolute;left:6%;right:6%;top:50%;margin-top:-130px;border-radius:18px;background:#0d1526;border:1px solid rgba(255,255,255,.14);box-shadow:0 40px 80px -30px #000;padding:18px;font-size:.9rem}
.nx-win .bar{display:flex;gap:6px;margin-bottom:14px}.nx-win .bar i{width:10px;height:10px;border-radius:50%;background:#334155}
.nx-row{display:flex;justify-content:space-between;padding:9px 12px;border-radius:10px;color:var(--mu)}
.nx-row:nth-child(odd){background:rgba(255,255,255,.04)}
.nx-pill{display:inline-block;margin-top:14px;padding:6px 14px;border-radius:999px;background:rgba(45,212,191,.15);color:var(--ac);font-weight:700;font-size:.85rem}
.nx-steps{display:flex;gap:10px;flex-wrap:wrap;margin-top:6px}.nx-steps span{padding:10px 16px;border-radius:12px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12)}
.nx-bars{display:flex;align-items:flex-end;gap:10px;height:110px;margin:6px 0 14px}.nx-bars i{flex:1;border-radius:8px 8px 0 0;background:linear-gradient(var(--ac),rgba(45,212,191,.2));height:var(--h)}
/* pinned personas */
.nx-pin{height:400vh;position:relative;background:radial-gradient(900px 560px at 72% 45%,color-mix(in srgb,var(--c) 30%,transparent),transparent 70%),#0b1729;transition:--c .9s}
.nx-pin-in{position:sticky;top:0;height:100svh;display:flex;align-items:center;overflow:hidden}
.nx-pin-grid{display:grid;grid-template-columns:1fr 1fr;gap:50px;align-items:center;width:100%}
.nx-tag{display:inline-block;padding:7px 16px;border-radius:999px;border:1px solid var(--c);color:var(--c);font-weight:700;transition:color .6s,border-color .6s}
.nx-pin h2{font-size:clamp(2rem,4.4vw,3.4rem);margin:20px 0 16px}
.nx-sw{transition:opacity .25s,transform .25s}.nx-sw.o{opacity:0;transform:translateY(10px)}
.nx-prog{height:4px;border-radius:4px;background:rgba(255,255,255,.12);margin-top:34px;max-width:360px;overflow:hidden}
.nx-prog i{display:block;height:100%;width:0;background:var(--c);transition:background .6s}
.nx-dots{display:flex;gap:18px;margin-top:16px;color:var(--mu);font-size:.9rem}.nx-dots span.on{color:var(--tx);font-weight:700}
.nx-lap{max-width:460px;margin:0 auto;perspective:1000px;transform:rotateY(-8deg) rotateX(3deg)}
.nx-lap .scr{position:relative;border:10px solid #34445f;border-radius:18px;background:#0a1120;aspect-ratio:16/10;overflow:hidden;box-shadow:0 40px 100px -30px var(--c);transition:box-shadow .8s}.nx-lap .lap-image{display:block;width:100%;height:100%;object-fit:cover}
.nx-lap .scr:before{content:'';position:absolute;top:4px;left:50%;width:36px;height:5px;transform:translateX(-50%);border-radius:999px;background:#111827;z-index:2}
.nx-lap .url{position:absolute;z-index:2;top:12px;left:14px;right:14px;padding:9px 16px 9px 42px;border:1px solid rgba(255,255,255,.16);border-radius:9px;background:rgba(10,17,32,.76);backdrop-filter:blur(9px);color:#e7f0ff;font-size:.78rem}.nx-lap .url:before{content:'● ● ●';position:absolute;left:15px;color:#5eead4;font-size:.55rem;letter-spacing:2px}
.nx-lap .pg{position:absolute;z-index:2;left:22px;bottom:18px;padding:12px 14px;border:1px solid rgba(255,255,255,.16);border-radius:12px;background:rgba(10,17,32,.8);backdrop-filter:blur(9px);min-width:55%}.nx-lap .pg b{display:block;font:700 1.15rem 'Space Grotesk',sans-serif;color:#fff;transition:color .6s}.nx-lap .pg:after{content:'LIVE';display:inline-block;margin-top:9px;padding:4px 8px;border:1px solid color-mix(in srgb,var(--c) 55%,transparent);border-radius:999px;color:var(--c);font:700 .62rem 'DM Sans',sans-serif;letter-spacing:.08em}
.nx-lap .ln{height:6px;border-radius:5px;background:rgba(231,241,255,.45);margin-top:8px}.nx-lap .base{position:relative;height:20px;margin:0 -34px;border-radius:0 0 24px 24px;background:linear-gradient(#63748e,#26344b);box-shadow:0 13px 20px rgba(0,0,0,.38)}.nx-lap .base:before{content:'';position:absolute;left:50%;top:2px;width:92px;height:7px;transform:translateX(-50%);border-radius:0 0 8px 8px;background:#0e1828}
/* story */
.nx-story{padding:140px 0;background:#0d192c}
.nx-line{font:700 clamp(2rem,5.6vw,4.4rem)/1.08 'Space Grotesk',sans-serif;letter-spacing:-.04em;margin-bottom:.35em;color:#8c9ab2;transition:color .8s}
.nx-line.in{color:var(--tx)}
.nx-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-top:80px}
.nx-stat{border-top:2px solid var(--ac);padding-top:18px}
.nx-stat strong{display:block;font:700 clamp(2.6rem,5vw,4rem)/1 'Space Grotesk',sans-serif}
.nx-stat span{color:var(--mu)}
/* light panels */
.nx-light{background:#101d33;color:var(--tx);border:1px solid rgba(190,202,224,.16);border-radius:28px;padding:36px;color-scheme:dark}
.nx-light .pricing-card{background:linear-gradient(160deg,#172842,#0e1b30)!important;border-color:rgba(190,202,224,.2)!important;color:var(--tx)!important}
.nx-light .pricing-card h3{color:var(--tx)!important}.nx-light .pricing-card p,.nx-light .pricing-card li{color:var(--mu)!important}.nx-light .pricing-card .price{color:#a7f3d0!important}.nx-light .plan-badge{color:var(--tx)!important}
.nx-tpl{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:50px}
.nx-tpl a{display:block;border-radius:20px;overflow:hidden;background:var(--bg2);border:1px solid rgba(255,255,255,.1);color:var(--tx);transition:transform .35s}
.nx-tpl a:hover{transform:translateY(-8px) rotateX(3deg)}
.nx-tpl img{width:100%;aspect-ratio:16/10;object-fit:cover;display:block;transition:transform .6s}
.nx-tpl a:hover img{transform:scale(1.06)}.nx-tpl div{overflow:hidden}.nx-tpl p{padding:16px 20px;font-weight:700}
/* cta */
.nx-cta{position:relative;text-align:center;padding:160px 0;overflow:hidden;background:radial-gradient(500px 360px at var(--mx,50%) var(--my,50%),rgba(45,212,191,.3),transparent 70%),#050810}
.nx-cta h2{font-size:clamp(2.4rem,6vw,4.8rem);max-width:15ch;margin:0 auto 20px}
.nx-cta .nx-actions{justify-content:center}
.nx-tr{position:fixed;left:0;top:0;width:9px;height:9px;margin:-4px 0 0 -4px;border-radius:50%;background:var(--ac);pointer-events:none;opacity:0;z-index:9999}
@media(max-width:991px){.nx-hero-grid,.nx-feat,.nx-pin-grid{grid-template-columns:1fr}.nx-aud,.nx-stats{grid-template-columns:1fr 1fr}.nx-tpl{grid-template-columns:1fr 1fr}.nx-feat.rev>.nx-vis{order:0}.nx-scene{height:430px;transform:scale(.8)}.nx-sec{padding:80px 0}.nx-feat{min-height:0;gap:30px}.nx-pin-grid{gap:20px}.nx-lap{max-width:300px}}
@media(max-width:575px){.nx-aud,.nx-tpl,.nx-stats{grid-template-columns:1fr}.nx-scene{transform:scale(.62);height:340px;margin:-30px 0}.nx-light{padding:16px}}
@media(prefers-reduced-motion:reduce){.nx *{animation:none!important;transition:none!important}.nx.js [data-r]{opacity:1;transform:none}}
</style>

<div class="nx">
<section class="nx-hero" id="nxHero">
  <div class="nx-wrap nx-hero-grid">
    <div data-r>
      <h1>Launch your website<br>in <em>minutes</em>, not weeks.</h1>
      <p class="nx-lede">Instaweb is Malawi-built hosting with a code editor in your browser, a file manager and one-click publishing. Start with a static site or a WordPress project.</p>
      <div class="nx-actions">
        <a href="<?= $primaryUrl ?>" class="nx-btn p">Start Building Free</a>
        <a href="#how" class="nx-btn g">See how it works</a>
      </div>
      <p class="nx-note">Create your account and publish your first site in minutes.</p>
      <div class="nx-who"><span>Developers</span><span>Students</span><span>Institutions</span><span>Organisations</span></div>
    </div>
    <div class="nx-scene" aria-hidden="true">
      <div class="nx-rig" id="nxRig"><div class="nx-float"><div class="nx-spin">
        <div class="nx-cloud" id="nxCloud"><span></span><svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg></div>
        <div class="nx-beam"></div>
        <div class="nx-device-stage">
          <div class="nx-phone"><div class="nx-phone-ui"><strong>Workspace</strong><small>Good morning, Nancy</small><div class="nx-phone-stats"><span>3<em>Websites</em></span><span>2<em>Published</em></span></div><div class="nx-phone-chart"><i style="--h:28%"></i><i style="--h:55%"></i><i style="--h:42%"></i><i style="--h:76%"></i><i style="--h:63%"></i></div></div></div>
          <div class="nx-laptop"><div class="nx-code-bar"><i></i><i></i><i></i><span>index.html</span></div><div class="nx-code"><b>&lt;main&gt;</b><br><span class="indent"><b>&lt;h1&gt;</b>Build online<b>&lt;/h1&gt;</b></span><br><span class="indent"><b>&lt;p&gt;</b><i>Publish with Instaweb</i><b>&lt;/p&gt;</b></span><br><span class="comment">// your site is live</span><br><b>&lt;/main&gt;</b></div></div>
          <span class="nx-device-chip sync">✓ Synced</span><span class="nx-device-chip live">● Live preview</span>
        </div>
      </div></div></div>
      <span class="nx-chip" style="left:2%;top:26%;--t:5.5s">Static sites</span>
      <span class="nx-chip" style="right:0;top:40%;--t:6.5s">WordPress</span>
      <span class="nx-chip" style="left:10%;bottom:12%;--t:7s">Analytics</span>
    </div>
  </div>
  <div class="nx-scroll" aria-hidden="true"></div>
</section>

<section class="nx-sec" id="audiences">
  <div class="nx-wrap">
    <div data-r><h2 class="nx-h2">One platform for everyone <em>building online</em>.</h2>
    <p class="nx-lede">From a first portfolio to a school or organisation website, Instaweb keeps hosting simple.</p></div>
    <div class="nx-aud">
      <?php foreach ([
        ['#38bdf8','Developers','Edit code in the browser with live preview, then publish static or WordPress projects.','<path d="M16 18l6-6-6-6M8 6l-6 6 6 6"/>'],
        ['#a78bfa','Students','Put coursework, portfolios and capstone projects online and share one link.','<path d="M22 10L12 5 2 10l10 5 10-5zM6 12v5c3 2 9 2 12 0v-5"/>'],
        ['#fbbf24','Institutions','Give schools and colleges a professional site that staff can keep up to date.','<path d="M3 21h18M5 21V10l7-5 7 5v11M9 21v-6h6v6"/>'],
        ['#34d399','Organisations','Churches, NGOs and businesses can publish news, events and contact details.','<path d="M3 21V7l9-4 9 4v14M9 21v-5h6v5M8 10h.01M12 10h.01M16 10h.01"/>'],
      ] as $i => $a): ?>
      <div class="nx-card" style="--k:<?= $a[0] ?>" data-r data-d="<?= $i ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $a[3] ?></svg>
        <h3><?= $e($a[1]) ?></h3><p><?= $e($a[2]) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="nx-sec" id="how" style="padding-top:40px">
  <div class="nx-wrap">
    <div class="nx-feat" style="--k:#2dd4bf">
      <div data-r="left"><h2 class="nx-h2">Create, upload, <em>publish</em>.</h2>
        <p class="nx-lede">Start a project, add your files and publish to your Instaweb address. Static hosting covers HTML, CSS, JavaScript, images and other assets.</p>
        <ul><li>Beginner-friendly controls</li><li>Drag in files and go live</li><li>Custom domains on Premium</li></ul></div>
      <div class="nx-vis" data-r="right"><div class="bk" data-p=".08"></div>
        <div class="nx-win" data-p="-.05"><div class="bar"><i></i><i></i><i></i></div>
          <div class="nx-row"><span>index.html</span><span>2 KB</span></div><div class="nx-row"><span>style.css</span><span>4 KB</span></div>
          <div class="nx-row"><span>app.js</span><span>3 KB</span></div><div class="nx-row"><span>images/</span><span>12 files</span></div>
          <span class="nx-pill">Published</span></div></div>
    </div>
    <div class="nx-feat rev" style="--k:#8b5cf6">
      <div data-r="right"><h2 class="nx-h2">Need a CMS? Start a <em>WordPress</em> project.</h2>
        <p class="nx-lede">Prepare a WordPress site for dynamic pages and database-backed content. Setup needs a host with PHP and database access.</p>
        <ul><li>WordPress files ready to set up</li><li>Manage them from your dashboard</li><li>Switch between site types per project</li></ul></div>
      <div class="nx-vis" data-r="left"><div class="bk" data-p=".08"></div>
        <div class="nx-win" data-p="-.05"><div class="bar"><i></i><i></i><i></i></div><p style="color:var(--mu);margin-bottom:12px">WordPress project</p>
          <div class="nx-steps"><span>Create</span><span>Upload</span><span>Finish setup</span></div><span class="nx-pill">Ready for setup</span></div></div>
    </div>
    <div class="nx-feat" style="--k:#38bdf8">
      <div data-r="left"><h2 class="nx-h2">Code, files and traffic in <em>one place</em>.</h2>
        <p class="nx-lede">A built-in editor with syntax highlighting and live preview, a file manager, analytics, billing controls and project settings.</p>
        <ul><li>Code editor with live preview</li><li>File uploads and storage insights</li><li>Traffic analytics and reports</li></ul></div>
      <div class="nx-vis" data-r="right"><div class="bk" data-p=".08"></div>
        <div class="nx-win" data-p="-.05"><div class="bar"><i></i><i></i><i></i></div><p style="color:var(--mu)">Visitors</p>
          <div class="nx-bars"><i style="--h:35%"></i><i style="--h:55%"></i><i style="--h:42%"></i><i style="--h:72%"></i><i style="--h:60%"></i><i style="--h:90%"></i></div>
          <span class="nx-pill">Analytics</span></div></div>
    </div>
  </div>
</section>

<section class="nx-pin" id="nxPin" aria-label="Who Instaweb is for">
  <div class="nx-pin-in"><div class="nx-wrap nx-pin-grid">
    <div>
      <span class="nx-tag" id="pTag">Developers</span>
      <h2 class="nx-sw" id="pH">Ship fast, edit in the browser.</h2>
      <p class="nx-lede nx-sw" id="pP">Use the built-in editor with live preview, manage files and publish without a server panel.</p>
      <div class="nx-prog"><i id="pBar"></i></div>
      <div class="nx-dots" id="pDots"><span class="on">Developers</span><span>Students</span><span>Institutions</span><span>Organisations</span></div>
    </div>
    <div class="nx-lap" aria-hidden="true"><div class="scr"><img class="lap-image" src="<?= htmlspecialchars($baseUrl . '/assets/images/coding-laptop.jpg', ENT_QUOTES, 'UTF-8') ?>" alt=""><div class="url" id="pUrl">yourname.dev</div>
      <div class="pg"><b id="pT">Portfolio</b><div class="ln" style="width:80%"></div><div class="ln" style="width:60%"></div><div class="ln" style="width:70%"></div></div></div><div class="base"></div></div>
  </div></div>
</section>

<section class="nx-story">
  <div class="nx-wrap">
    <p class="nx-line" data-r>Websites shouldn't need a big budget.</p>
    <p class="nx-line" data-r data-d=".15">Or a server you have to understand.</p>
    <p class="nx-line" data-r data-d=".3">Just your files, one dashboard and a link to share.</p>
    <div class="nx-stats">
      <div class="nx-stat" data-r><strong data-n="3">0</strong><span>steps to go live</span></div>
      <div class="nx-stat" data-r data-d=".1"><strong data-n="7">0</strong><span>built-in tools</span></div>
      <div class="nx-stat" data-r data-d=".2"><strong data-n="2">0</strong><span>site types</span></div>
      <div class="nx-stat" data-r data-d=".3"><strong data-n="1">0</strong><span>dashboard for everything</span></div>
    </div>
  </div>
</section>

<section class="nx-sec" id="pricing">
  <div class="nx-wrap">
    <div data-r><h2 class="nx-h2">Simple <em>pricing</em>.</h2></div>
    <div class="nx-light" style="margin-top:40px" data-r="zoom"><?php include APP_PATH . '/Views/marketing/partials/pricing-cards.php'; ?></div>
  </div>
</section>

<section class="nx-sec" id="templates" style="padding-top:20px">
  <div class="nx-wrap">
    <div data-r><h2 class="nx-h2">Start from a <em>template</em>.</h2></div>
    <div class="nx-tpl">
      <?php foreach ([
        ['Business Website','461073'],['Portfolio','326514'],['Church Website','69432'],['School Website','5621952'],['Blog','7394719'],['Landing Page','3584973'],
      ] as $i => $t): ?>
      <a href="<?= $templatesUrl ?>" data-r data-d="<?= ($i % 3) * .1 ?>"><div><img loading="lazy" src="https://images.pexels.com/photos/<?= $t[1] ?>/pexels-photo-<?= $t[1] ?>.jpeg?auto=compress&cs=tinysrgb&w=800" alt="<?= $e($t[0]) ?> template"></div><p><?= $e($t[0]) ?></p></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="nx-cta" id="nxCta">
  <div class="nx-wrap">
    <h2 data-r>Your website is one account away.</h2>
    <p class="nx-lede" style="margin:0 auto 34px" data-r>Create your account and publish today.</p>
    <div class="nx-actions" data-r><a href="<?= $primaryUrl ?>" class="nx-btn p">Create Account</a><a href="<?= $loginUrl ?>" class="nx-btn g">Log In</a></div>
  </div>
</section>
</div>

<script>
(function(){
var root=document.querySelector('.nx');if(!root)return;root.classList.add('js');
var rm=matchMedia('(prefers-reduced-motion:reduce)').matches,$=function(s){return root.querySelector(s)};
// scroll reveal + counters
var io=new IntersectionObserver(function(es){es.forEach(function(x){if(!x.isIntersecting)return;var t=x.target;t.classList.add('in');io.unobserve(t);var n=t.querySelector('[data-n]');if(n)count(n)})},{threshold:.18});
root.querySelectorAll('[data-r]').forEach(function(el){if(el.dataset.d)el.style.setProperty('--d',el.dataset.d+(el.dataset.d.indexOf('.')>-1?'s':'s'));io.observe(el)});
function count(el){var to=+el.dataset.n,s=null;function f(t){s=s||t;var p=Math.min(1,(t-s)/1200);el.textContent=Math.round(to*(1-Math.pow(1-p,3)));if(p<1)requestAnimationFrame(f)}rm?el.textContent=to:requestAnimationFrame(f)}
// scroll-linked: parallax + pinned personas
var par=[].slice.call(root.querySelectorAll('[data-p]')),pin=$('#nxPin'),hero=$('#nxHero'),rig=$('#nxRig'),cloud=$('#nxCloud'),cur=-1,busy=false;
var P=[['#38bdf8','Developers','Ship fast, edit in the browser.','Use the built-in editor with live preview, manage files and publish static or WordPress projects without a server panel.','yourname.dev','Portfolio'],
['#a78bfa','Students','Your projects, live for everyone.','Put coursework, portfolios and capstone projects online and share a single link with lecturers and employers.','my-project.site','Capstone Project'],
['#fbbf24','Institutions','A proper home for your school or college.','Publish a clean, professional website that staff can keep updated without hiring a developer.','your-school.edu','Our School'],
['#34d399','Organisations','Churches, NGOs and businesses online.','Share your story, events and contact details, and manage hosting, analytics and billing from one dashboard.','your-group.org','Our Community']];
var dots=[].slice.call(document.querySelectorAll('#pDots span'));
function setP(i){if(i===cur)return;cur=i;var d=P[i];pin.style.setProperty('--c',d[0]);var sw=[$('#pH'),$('#pP')];sw.forEach(function(e){e.classList.add('o')});
setTimeout(function(){$('#pTag').textContent=d[1];$('#pH').textContent=d[2];$('#pP').textContent=d[3];$('#pUrl').textContent=d[4];$('#pT').textContent=d[5];sw.forEach(function(e){e.classList.remove('o')})},rm?0:220);
dots.forEach(function(x,k){x.classList.toggle('on',k===i)})}
function update(){busy=false;var vh=innerHeight;
if(!rm)par.forEach(function(el){var r=el.parentNode.getBoundingClientRect();el.style.transform='translate3d(0,'+((r.top+r.height/2-vh/2)*el.dataset.p).toFixed(1)+'px,0)'});
if(!rm&&hero&&rig&&cloud){var heroBox=hero.getBoundingClientRect(),heroScroll=Math.min(1,Math.max(0,-heroBox.top/Math.max(1,hero.offsetHeight-vh*.32)));
rig.style.setProperty('--scroll-y',(heroScroll*74).toFixed(1)+'px');rig.style.setProperty('--scroll-x',(heroScroll*-24).toFixed(1)+'px');rig.style.setProperty('--scroll-rx',(heroScroll*16).toFixed(1)+'deg');rig.style.setProperty('--scroll-ry',(heroScroll*-42).toFixed(1)+'deg');
cloud.style.setProperty('--cloud-x',(heroScroll*-72).toFixed(1)+'px');cloud.style.setProperty('--cloud-y',(heroScroll*112).toFixed(1)+'px');cloud.style.setProperty('--cloud-scale',(1-heroScroll*.2).toFixed(3))}
var r=pin.getBoundingClientRect(),p=Math.min(1,Math.max(0,-r.top/(r.height-vh)));$('#pBar').style.width=(p*100)+'%';setP(Math.min(3,Math.floor(p*4)))}
function tick(){if(!busy){busy=true;requestAnimationFrame(update)}}
addEventListener('scroll',tick,{passive:true});addEventListener('resize',tick);update();
// hero tilt + CTA glow
if(!rm){hero.addEventListener('mousemove',function(e){var b=hero.getBoundingClientRect();rig.style.setProperty('--ry',((e.clientX-b.left)/b.width-.5)*18+'deg');rig.style.setProperty('--rx',(-((e.clientY-b.top)/b.height-.5))*12+'deg')});
var cta=$('#nxCta');cta.addEventListener('mousemove',function(e){var b=cta.getBoundingClientRect();cta.style.setProperty('--mx',(e.clientX-b.left)+'px');cta.style.setProperty('--my',(e.clientY-b.top)+'px')})}
// desktop cursor trail
if(!rm&&matchMedia('(hover:hover) and (pointer:fine)').matches){var dots2=[],k=0;for(var i=0;i<8;i++){var d=document.createElement('i');d.className='nx-tr';document.body.appendChild(d);dots2.push(d)}
addEventListener('mousemove',function(e){var d=dots2[k++%8],t='translate('+e.clientX+'px,'+e.clientY+'px)';d.animate([{opacity:.7,transform:t+' scale(1)'},{opacity:0,transform:t+' scale(.2)'}],{duration:650})})}
})();
</script>
