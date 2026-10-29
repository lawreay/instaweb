<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$websiteId = (int) $website['id'];
$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

$avatarPath = trim((string) ($portfolio['avatar_path'] ?? ''));
$avatarUrl = $avatarPath !== '' && !str_contains($avatarPath, '..')
    && preg_match('#^uploads/[A-Za-z0-9_./-]+$#', $avatarPath)
    ? $baseUrl . '/' . ltrim($avatarPath, '/')
    : '';

$templateDescriptions = [
    'classic' => 'Clear, adaptable and familiar.',
    'editorial' => 'Typography-led, like a printed feature.',
    '3d' => 'Immersive and technical.',
    'minimal' => 'Quiet, focused and content-first.',
    'noir' => 'Bold, dark and editorial.',
    'aurora' => 'Luminous motion and layered colour.',
    'studio' => 'Warm, creative and tactile.',
    'orbit' => 'A technical mission-control aesthetic.',
    'prism' => 'Expressive colour and confident shapes.',
    'archive' => 'A paper-led creative catalogue.',
];

$currentTemplate = (string) ($portfolio['template_key'] ?? 'classic');
$isPremiumUser = (bool) ($isPremiumUser ?? false);
$premiumTemplateKeys = is_array($premiumTemplateKeys ?? null) ? $premiumTemplateKeys : [];
$premiumPlanPrice = trim((string) ($premiumPlanPrice ?? '')) ?: 'See plan pricing';
$premiumUpgradeEnabled = (bool) ($premiumUpgradeEnabled ?? false);
$ownPreviewUrl = $baseUrl . '/dashboard/websites/preview?id=' . $websiteId;
$templatePreviewUrls = is_array($templatePreviewUrls ?? null) ? $templatePreviewUrls : [];
$initialPreviewUrl = $templatePreviewUrls[$currentTemplate] ?? $ownPreviewUrl;

/* ---------- template helpers ---------- */
// The controller builds this list from the administrator-managed template tiers.
// Any template not in it is Free, including administrator-created Free templates.
$isPremiumTemplate = static fn (string $key): bool => in_array($key, $premiumTemplateKeys, true);

// "Aurora: night-sky portfolio ..." or "Ledger — professional finance ..." -> [short name, long description]
$splitLabel = static function (string $label): array {
    if (preg_match('/^(.*?)(?::\s+|\s+[\x{2014}\x{2013}-]\s+)(.+)$/u', $label, $m)) {
        return [trim($m[1]), trim($m[2])];
    }
    return [trim($label), ''];
};

// Thumbnail colours: bg, ink, shape, card. Matched by substring so custom keys (custom-bento, ...) get a fitting look.
$palettes = [
    'classic'   => ['#e9eff1', '#25343b', '#b8ced3', '#f9fcfb'],
    'editorial' => ['#eee1ce', '#432a1f', '#d3af86', '#f9f0e3'],
    '3d'        => ['#151c35', '#cbd7ff', '#7359ef', '#283763'],
    'minimal'   => ['#fafafa', '#161616', '#dedede', '#ffffff'],
    'noir'      => ['#121212', '#f4eee4', '#6d5949', '#292622'],
    'aurora'    => ['#14224a', '#caffeb', '#7c5cff', '#2b4a78'],
    'studio'    => ['#ead9c2', '#613d29', '#c88150', '#f8eedf'],
    'orbit'     => ['#08222d', '#8bf1f7', '#008ba2', '#124655'],
    'prism'     => ['#f6c6d6', '#43204f', '#ffd064', '#fff4d6'],
    'archive'   => ['#e8decc', '#473d2e', '#c5a87d', '#f6f0e4'],
    'glass'     => ['#2a1f66', '#ffffff', '#ff4f9a', '#5b4aa8'],
    'neumorph'  => ['#e6e9ef', '#2a3043', '#d3d8e2', '#f3f5f9'],
    'mono'      => ['#f2f2f2', '#111111', '#bdbdbd', '#ffffff'],
    'cyber'     => ['#07070f', '#00f0ff', '#ff2e88', '#1b1b36'],
    'bento'     => ['#efece6', '#1c1b1a', '#ffd9c9', '#ffffff'],
    'material'  => ['#eaddff', '#21005d', '#cbb8f5', '#fef7ff'],
    'horizon'   => ['#eae5ff', '#17213c', '#ffd493', '#ffffff'],
    'ledger'    => ['#f1ece4', '#172b3a', '#e0b99f', '#ffffff'],
    'graduate'  => ['#e4efe6', '#1f3b2c', '#b5d6bd', '#f8fcf8'],
];
$miniStyle = static function (string $key) use ($palettes, $escape): string {
    $lower = strtolower($key);
    $colors = null;
    foreach ($palettes as $needle => $palette) {
        if (str_contains($lower, $needle)) {
            $colors = $palette;
            break;
        }
    }
    if ($colors === null) {
        $hue = crc32($key) % 360;
        $colors = ["hsl($hue 40% 92%)", "hsl($hue 45% 22%)", "hsl($hue 50% 76%)", '#ffffffcc'];
    }
    return $escape("--m-bg:{$colors[0]};--m-ink:{$colors[1]};--m-shape:{$colors[2]};--m-card:{$colors[3]}");
};

// Current template first, so it is always visible when the list is collapsed.
$orderedTemplates = $templates;
if (isset($orderedTemplates[$currentTemplate])) {
    $orderedTemplates = [$currentTemplate => $orderedTemplates[$currentTemplate]] + $orderedTemplates;
}
$totalTemplates = count($orderedTemplates);
$premiumCount = 0;
foreach ($orderedTemplates as $k => $_label) {
    if ($isPremiumTemplate((string) $k)) {
        $premiumCount++;
    }
}
$freeCount = $totalTemplates - $premiumCount;

[$currentShort] = $splitLabel((string) ($templates[$currentTemplate] ?? ucfirst($currentTemplate)));

$displayName = trim((string) ($portfolio['display_name'] ?? ''));
$headline = trim((string) ($portfolio['headline'] ?? ''));
$doneCount = ($displayName !== '' ? 1 : 0) + ($headline !== '' ? 1 : 0) + ($avatarUrl !== '' ? 1 : 0);
?>
<style>
.portfolio-setup{--ps-ink:#17191d;--ps-muted:#667085;--ps-line:#e4e7ec;--ps-soft:#f8fafc;--ps-accent:#f97316;--ps-accent-ink:#9a3412;--ps-accent-soft:#fff4ea;--ps-ok:#16804a;--ps-err:#b42318;color:var(--ps-ink)}
.portfolio-setup *{box-sizing:border-box}
.portfolio-setup .ps-visually-hidden{position:absolute;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;border:0}

/* heading */
.portfolio-setup .ps-heading{display:flex;justify-content:space-between;gap:1.5rem;align-items:flex-end;margin-bottom:1.5rem}
.portfolio-setup .ps-eyebrow{font-size:.72rem;letter-spacing:.15em;font-weight:800;text-transform:uppercase;color:var(--ps-muted);margin:0 0 .55rem}
.portfolio-setup .ps-heading h1{font-size:clamp(1.8rem,3vw,2.5rem);font-weight:800;letter-spacing:-.04em;margin:0}
.portfolio-setup .ps-heading p{color:var(--ps-muted);margin:.55rem 0 0;max-width:42rem}
.portfolio-setup .ps-actions{display:flex;flex-wrap:wrap;gap:.65rem}

/* panels */
.portfolio-setup .ps-panel{background:var(--bs-body-bg,#fff);border:1px solid var(--ps-line);border-radius:1.1rem;box-shadow:0 .6rem 1.8rem rgba(15,23,42,.04);min-width:0}
.portfolio-setup .ps-panel-head{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;padding:1.15rem clamp(1rem,2vw,1.5rem);border-bottom:1px solid var(--ps-line)}
.portfolio-setup .ps-title{display:flex;gap:.8rem;align-items:flex-start}
.portfolio-setup .ps-step{flex:0 0 auto;display:grid;place-items:center;width:1.9rem;height:1.9rem;border-radius:50%;background:var(--ps-ink);color:#fff;font-size:.85rem;font-weight:800}
.portfolio-setup .ps-title h2{font-size:1.1rem;font-weight:800;letter-spacing:-.02em;margin:.1rem 0 0}
.portfolio-setup .ps-title p{margin:.25rem 0 0;color:var(--ps-muted);font-size:.84rem;line-height:1.5}

/* step 1: essentials */
.portfolio-setup .ps-profile{margin-bottom:1.25rem}
.portfolio-setup .ps-meter{display:flex;flex-direction:column;align-items:flex-end;gap:.35rem;min-width:11rem}
.portfolio-setup .ps-meter-text{font-size:.78rem;font-weight:700;color:var(--ps-muted)}
.portfolio-setup .ps-meter-bar{display:block;width:100%;height:.35rem;border-radius:9px;background:#eceff3;overflow:hidden}
.portfolio-setup .ps-meter-bar i{display:block;height:100%;border-radius:9px;background:var(--ps-accent);transition:width .25s ease}
.portfolio-setup .ps-status{font-size:.78rem;color:var(--ps-muted);min-height:1.1rem}
.portfolio-setup .ps-status[data-state="saved"]{color:var(--ps-ok)}
.portfolio-setup .ps-status[data-state="error"]{color:var(--ps-err)}
.portfolio-setup .ps-profile-grid{display:grid;grid-template-columns:auto minmax(0,1fr);gap:clamp(1rem,3vw,2rem);align-items:start;padding:clamp(1rem,2vw,1.5rem)}
.portfolio-setup .ps-photo{display:flex;flex-direction:column;align-items:center;gap:.65rem;width:8.5rem}
.portfolio-setup .ps-avatar{display:block;width:8.5rem;height:10.25rem;object-fit:cover;border-radius:999px 999px .9rem .9rem;background:#f1f3f5;border:1px solid var(--ps-line)}
.portfolio-setup .ps-avatar[hidden]{display:none}
.portfolio-setup .ps-avatar-empty{display:grid;place-items:center;padding:.75rem;color:var(--ps-muted);font-size:.76rem;line-height:1.4;text-align:center;border-style:dashed}
.portfolio-setup .ps-photo form{width:100%;text-align:center}
.portfolio-setup .ps-photo-btn{width:100%;cursor:pointer}
.portfolio-setup .ps-photo:focus-within .ps-photo-btn{outline:3px solid #f9731640;outline-offset:2px}
.portfolio-setup .ps-hint{margin:0;color:var(--ps-muted);font-size:.72rem;line-height:1.4;text-align:center}
.portfolio-setup .ps-fields{display:grid;gap:1rem;min-width:0}
.portfolio-setup .ps-field{display:block;font-weight:700;font-size:.88rem;margin:0}
.portfolio-setup .ps-field input{display:block;width:100%;margin-top:.4rem;border:1px solid #d0d5dd;border-radius:.7rem;padding:.8rem .9rem;font:inherit;font-weight:400;background:var(--bs-body-bg,#fff);color:inherit}
.portfolio-setup .ps-field input:focus{outline:3px solid #f9731626;border-color:var(--ps-accent)}
.portfolio-setup .ps-field small{display:block;margin-top:.35rem;color:var(--ps-muted);font-weight:400;font-size:.76rem}

/* layout */
.portfolio-setup .ps-layout{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,1fr);gap:1.25rem;align-items:start}

/* step 2: gallery */
.portfolio-setup .ps-active-control{flex:0 0 auto;display:inline-flex;align-items:center;gap:.35rem;padding:.45rem .75rem;border:1px solid #fed7aa;border-radius:999px;background:var(--ps-accent-soft);color:var(--ps-accent-ink);font-size:.78rem;font-weight:800;white-space:nowrap}
.portfolio-setup .ps-toolbar{display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;justify-content:space-between;padding:.9rem clamp(1rem,2vw,1.5rem);border-bottom:1px solid var(--ps-line);background:var(--ps-soft)}
.portfolio-setup .ps-search{flex:1 1 11rem;min-width:0}
.portfolio-setup .ps-search input{width:100%;border:1px solid #d0d5dd;border-radius:999px;padding:.55rem .95rem;font:inherit;font-size:.88rem;background:#fff;color:inherit}
.portfolio-setup .ps-search input:focus{outline:3px solid #f9731626;border-color:var(--ps-accent)}
.portfolio-setup .ps-chips{display:flex;flex-wrap:wrap;gap:.4rem}
.portfolio-setup .ps-chip{display:inline-flex;align-items:center;gap:.4rem;min-height:2.25rem;padding:0 .8rem;border:1px solid #d0d5dd;border-radius:999px;background:#fff;color:#344054;font:inherit;font-size:.82rem;font-weight:700;cursor:pointer}
.portfolio-setup .ps-chip b{font-weight:700;color:var(--ps-muted);font-size:.74rem}
.portfolio-setup .ps-chip[aria-pressed="true"]{border-color:var(--ps-ink);background:var(--ps-ink);color:#fff}
.portfolio-setup .ps-chip[aria-pressed="true"] b{color:#d0d5dd}
.portfolio-setup .ps-template-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(9.75rem,1fr));gap:.85rem;padding:clamp(1rem,2vw,1.5rem)}
.portfolio-setup .ps-template{position:relative;display:flex;flex-direction:column;min-width:0;padding:.5rem;border:1px solid var(--ps-line);border-radius:.95rem;background:#fff;color:inherit;text-align:left;cursor:pointer;transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease}
.portfolio-setup .ps-template[hidden]{display:none}
.portfolio-setup .ps-template:hover{transform:translateY(-2px);border-color:#fdba74;box-shadow:0 .7rem 1.4rem rgba(124,45,18,.1)}
.portfolio-setup .ps-template input{position:absolute;opacity:0;pointer-events:none}
.portfolio-setup .ps-template:focus-within{outline:3px solid #f9731640;outline-offset:2px}
.portfolio-setup .ps-template.is-selected,.portfolio-setup .ps-template input:checked ~ .ps-template-check{--ps-on:1}
.portfolio-setup .ps-template.is-selected{border-color:var(--ps-accent);box-shadow:0 0 0 3px #f9731626}
.portfolio-setup .ps-mini{position:relative;isolation:isolate;display:flex;flex-direction:column;justify-content:flex-start;height:5.6rem;padding:.8rem;overflow:hidden;border-radius:.65rem;background:var(--m-bg);color:var(--m-ink)}
.portfolio-setup .ps-mini:before{content:'';position:absolute;z-index:-1;right:-1.2rem;bottom:-1.8rem;width:5rem;height:5rem;border-radius:50%;background:var(--m-shape);opacity:.85}
.portfolio-setup .ps-mini:after{content:'';position:absolute;z-index:-1;left:.8rem;right:.8rem;bottom:.7rem;height:1.9rem;border-radius:.4rem;background:var(--m-card)}
.portfolio-setup .ps-mini span{display:block;border-radius:99px;background:currentColor}
.portfolio-setup .ps-mini span:nth-child(1){width:2rem;height:.22rem;opacity:.8}
.portfolio-setup .ps-mini span:nth-child(2){width:62%;height:.62rem;margin-top:.55rem}
.portfolio-setup .ps-mini span:nth-child(3){width:42%;height:.22rem;margin-top:.35rem;opacity:.5}
.portfolio-setup .ps-tier{position:absolute;top:.95rem;left:.95rem;z-index:2;padding:.2rem .45rem;border-radius:999px;font-size:.6rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;background:#fff;box-shadow:0 .1rem .4rem #0002}
.portfolio-setup .ps-tier--free{color:#166534}
.portfolio-setup .ps-tier--premium{color:#92400e}
.portfolio-setup .ps-template-check{position:absolute;top:.9rem;right:.9rem;z-index:2;display:grid;place-items:center;width:1.4rem;height:1.4rem;border:1px solid #d0d5dd;border-radius:50%;background:#fff;color:transparent;font-size:.8rem;font-weight:900;transition:.16s ease}
.portfolio-setup .ps-template.is-selected .ps-template-check{border-color:var(--ps-accent);background:var(--ps-accent);color:#fff}
.portfolio-setup .ps-template-name{display:block;margin:.65rem .2rem .15rem;font-weight:800;font-size:.9rem;letter-spacing:-.01em;overflow-wrap:anywhere}
.portfolio-setup .ps-template-desc{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin:0 .2rem .35rem;color:var(--ps-muted);font-size:.74rem;line-height:1.4}
.portfolio-setup .ps-template-active{align-self:flex-start;margin:auto .2rem .1rem;padding:.2rem .45rem;border-radius:999px;background:#e8f7ee;color:#166534;font-size:.65rem;font-weight:800}
.portfolio-setup .ps-template-active[hidden]{display:none}
.portfolio-setup .ps-empty{margin:0;padding:1.5rem;text-align:center;color:var(--ps-muted);font-size:.9rem}
.portfolio-setup .ps-empty[hidden]{display:none}
.portfolio-setup .ps-more{display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;justify-content:space-between;padding:0 clamp(1rem,2vw,1.5rem) 1.15rem}
.portfolio-setup .ps-more[hidden],.portfolio-setup #ps-toggle[hidden]{display:none}
.portfolio-setup .ps-count{font-size:.78rem;color:var(--ps-muted)}
.portfolio-setup .ps-next{display:flex;flex-wrap:wrap;gap:.75rem 1.25rem;align-items:center;justify-content:space-between;padding:1.15rem clamp(1rem,2vw,1.5rem);border-top:1px solid var(--ps-line);background:linear-gradient(105deg,#fff 0%,#fff8f2 100%);border-radius:0 0 1.1rem 1.1rem}
.portfolio-setup .ps-next strong{display:block;font-size:.95rem}
.portfolio-setup .ps-next span{display:block;color:var(--ps-muted);font-size:.82rem;margin-top:.15rem}

/* preview: pin the entire aside, not only its inner preview content. */
.portfolio-setup.client-dashboard,.portfolio-setup.client-dashboard>.client-main{overflow:visible}
.portfolio-setup.client-dashboard>.client-main>.ps-layout>aside.ps-preview-panel{position:sticky;top:calc(72px + 1rem);z-index:10;align-self:start;max-height:calc(100dvh - 72px - 2rem);overflow:auto}
.portfolio-setup .ps-preview-head{display:flex;flex-wrap:wrap;gap:.75rem;align-items:center;justify-content:space-between;padding:1rem 1.15rem;border-bottom:1px solid var(--ps-line)}
.portfolio-setup .ps-preview-head h2{font-size:1.05rem;font-weight:800;margin:0}
.portfolio-setup .ps-preview-head p{margin:.2rem 0 0;color:var(--ps-muted);font-size:.74rem}
.portfolio-setup .ps-device{display:inline-flex;padding:.2rem;border:1px solid var(--ps-line);border-radius:999px;background:var(--ps-soft)}
.portfolio-setup .ps-device button{min-height:2rem;padding:0 .85rem;border:0;border-radius:999px;background:transparent;color:#475467;font:inherit;font-size:.78rem;font-weight:700;cursor:pointer}
.portfolio-setup .ps-device button[aria-pressed="true"]{background:#fff;color:var(--ps-ink);box-shadow:0 .1rem .4rem #0002}
.portfolio-setup .ps-preview-now{display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin:.9rem 1.15rem;padding:.65rem .8rem;border:1px solid #f1d5bf;border-radius:.7rem;background:#fff9f5}
.portfolio-setup .ps-preview-now span.ps-label{display:block;color:#875337;font-size:.66rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.portfolio-setup .ps-preview-now strong{display:block;color:#6f2d0b;font-size:.95rem;overflow-wrap:anywhere}
.portfolio-setup .ps-state{flex:0 0 auto;padding:.25rem .55rem;border-radius:999px;background:#fff;border:1px solid #f1d5bf;color:#875337;font-size:.7rem;font-weight:800;white-space:nowrap}
.portfolio-setup .ps-state[data-tone="active"]{background:#e8f7ee;border-color:#bfe6cc;color:#166534}
.portfolio-setup .ps-state[data-tone="locked"]{background:#fef3c7;border-color:#fde68a;color:#92400e}
.portfolio-setup .ps-preview-stage{margin:0 1.15rem;overflow:hidden;border:1px solid #dfe5eb;border-radius:.8rem;background:#edf1f5}
.portfolio-setup .ps-browser{display:flex;align-items:center;gap:.35rem;padding:.5rem .75rem;background:#f8fafc;border-bottom:1px solid var(--ps-line);font-size:.72rem;color:var(--ps-muted)}
.portfolio-setup .ps-browser i{width:.55rem;height:.55rem;border-radius:50%;background:#d0d5dd}
.portfolio-setup .ps-browser span{margin-left:.4rem;white-space:nowrap}
.portfolio-setup .ps-viewport{position:relative;display:flex;justify-content:center;align-items:flex-start;background:#edf1f5}
.portfolio-setup .ps-viewport[data-device="mobile"]{padding:16px}
.portfolio-setup .ps-frame-wrap{position:relative;overflow:hidden;background:#fff}
.portfolio-setup .ps-viewport[data-device="mobile"] .ps-frame-wrap{border-radius:1.1rem;box-shadow:0 0 0 6px #17191d,0 .8rem 2rem #0003}
.portfolio-setup .ps-preview-frame{display:block;max-width:none;border:0;background:#fff;transform-origin:top left}
.portfolio-setup .ps-loading{position:absolute;inset:0;z-index:2;display:grid;place-items:center;background:#ffffffd9;color:var(--ps-muted);font-size:.82rem;font-weight:700;transition:opacity .2s}
.portfolio-setup .ps-loading[hidden]{display:none}
.portfolio-setup .ps-preview-foot{display:grid;gap:.75rem;padding:1rem 1.15rem}
.portfolio-setup .ps-lock-note{margin:0;padding:.65rem .8rem;border-left:3px solid #f59e0b;border-radius:0 .5rem .5rem 0;background:#fffbeb;color:#78350f;font-size:.8rem;line-height:1.45}
.portfolio-setup .ps-lock-note[hidden]{display:none}
.portfolio-setup .ps-apply{min-height:2.9rem;font-weight:800}
.portfolio-setup .ps-foot-links{display:flex;flex-wrap:wrap;gap:.4rem 1rem;font-size:.8rem}
.portfolio-setup .ps-foot-links button{padding:0;border:0;background:none;color:inherit;font:inherit;text-decoration:underline;cursor:pointer}
.portfolio-setup .ps-preview-note{margin:0;padding:0 1.15rem 1.15rem;color:var(--ps-muted);font-size:.74rem;line-height:1.5}

/* upgrade modal and toast (inside the wrapper so these styles apply) */
.portfolio-setup .ps-toast{position:fixed;right:1.25rem;bottom:1.25rem;z-index:2000;display:flex;align-items:flex-start;gap:.7rem;max-width:min(26rem,calc(100vw - 2rem));padding:.9rem 1rem;border-radius:.8rem;background:#17211b;color:#fff;box-shadow:0 12px 35px #0003;font-size:.9rem;font-weight:650;opacity:0;transform:translateY(10px);pointer-events:none;transition:opacity .18s ease,transform .18s ease}
.portfolio-setup .ps-toast.is-visible{opacity:1;transform:translateY(0)}
.portfolio-setup .ps-toast[data-state="error"]{background:#8f1d18}
.portfolio-setup .ps-modal[hidden]{display:none}
.portfolio-setup .ps-modal{position:fixed;inset:0;z-index:3000;display:grid;place-items:center;padding:1rem}
.portfolio-setup .ps-modal-backdrop{position:absolute;inset:0;background:#111827b8;backdrop-filter:blur(3px)}
.portfolio-setup .ps-modal-card{position:relative;width:min(100%,31rem);max-height:min(90vh,48rem);overflow:auto;background:#fff;color:#17191d;border-radius:1.15rem;padding:1.5rem;box-shadow:0 24px 80px #0004}
.portfolio-setup .ps-modal-close{border:0;background:#f3f4f6;border-radius:999px;width:2.25rem;height:2.25rem;font-size:1.2rem;line-height:1;cursor:pointer}
.portfolio-setup .ps-modal-kicker{display:inline-flex;align-items:center;gap:.4rem;font-size:.75rem;font-weight:850;text-transform:uppercase;letter-spacing:.1em;color:#9a3412;background:#fff1e8;border-radius:999px;padding:.35rem .65rem}
.portfolio-setup .ps-modal-price{font-size:1.8rem;font-weight:900;letter-spacing:-.04em;margin:.6rem 0}
.portfolio-setup .ps-modal-benefits{display:grid;gap:.7rem;list-style:none;padding:0;margin:1.25rem 0}
.portfolio-setup .ps-modal-benefits li{display:flex;gap:.65rem;align-items:flex-start}
.portfolio-setup .ps-modal-benefits li:before{content:'\2713';font-weight:900;color:#15803d}
.portfolio-setup .ps-modal-footnote{font-size:.78rem;color:#6b7280;line-height:1.5}
.portfolio-setup .ps-modal-primary{display:flex;align-items:center;justify-content:center;gap:.5rem;width:100%;border:0;border-radius:.75rem;background:#f97316;color:#fff;font-weight:850;padding:.9rem 1rem}
.portfolio-setup .ps-modal-primary:disabled{opacity:.55;cursor:not-allowed}

.portfolio-setup a:focus-visible,.portfolio-setup button:focus-visible{outline:3px solid #f9731666;outline-offset:2px}

@media(max-width:1100px){
  .portfolio-setup .ps-layout{grid-template-columns:minmax(0,1fr)}
  .portfolio-setup .ps-preview-panel{order:-1}
}
@media(max-width:640px){
  .portfolio-setup .ps-heading{display:block}
  .portfolio-setup .ps-heading .ps-actions{margin-top:1rem}
  .portfolio-setup .ps-panel-head{flex-direction:column}
  .portfolio-setup .ps-meter{align-items:stretch;width:100%}
  .portfolio-setup .ps-profile-grid{grid-template-columns:minmax(0,1fr)}
  .portfolio-setup .ps-photo{flex-direction:row;align-items:center;width:100%}
  .portfolio-setup .ps-avatar{width:5rem;height:6rem;flex:0 0 auto}
  .portfolio-setup .ps-photo form{text-align:left}
  .portfolio-setup .ps-photo-btn{width:auto}
  .portfolio-setup .ps-hint{text-align:left}
  .portfolio-setup .ps-template-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:.65rem}
  .portfolio-setup .ps-next .btn{width:100%}
  .portfolio-setup.client-dashboard>.client-main>.ps-layout>aside.ps-preview-panel{position:static;top:auto;max-height:none}
}
@media(prefers-reduced-motion:reduce){
  .portfolio-setup *{transition:none}
}
</style>

<section class="client-dashboard iw-client-page portfolio-setup">
  <div class="ps-toast" id="portfolio-toast" role="status" aria-live="polite" data-state="success"><span id="portfolio-toast-icon" aria-hidden="true">&#10003;</span><span id="portfolio-toast-message">Changes saved successfully.</span></div>

  <?php if (!$isPremiumUser): ?>
  <div class="ps-modal" id="portfolio-upgrade-modal" hidden aria-hidden="true">
    <div class="ps-modal-backdrop" data-upgrade-close></div>
    <section class="ps-modal-card" role="dialog" aria-modal="true" aria-labelledby="portfolio-upgrade-title" aria-describedby="portfolio-upgrade-description">
      <div class="d-flex align-items-start justify-content-between gap-3">
        <span class="ps-modal-kicker"><i data-lucide="crown" aria-hidden="true"></i> InstaWeb Premium</span>
        <button type="button" class="ps-modal-close" data-upgrade-close aria-label="Close upgrade dialog">&times;</button>
      </div>
      <h2 id="portfolio-upgrade-title" class="mt-3 mb-2 fw-bold">Make your portfolio stand out.</h2>
      <p id="portfolio-upgrade-description" class="text-secondary mb-2">Unlock every design and publish a polished portfolio without the Free-plan watermark.</p>
      <div class="ps-modal-price"><?= $escape($premiumPlanPrice) ?></div>
      <ul class="ps-modal-benefits">
        <li>Access all Premium and administrator-created templates</li>
        <li>Remove the Powered by InstaWeb watermark</li>
        <li>Export your portfolio to PDF</li>
        <li>Unlock the other benefits included in your Premium plan</li>
      </ul>
      <?php if ($premiumUpgradeEnabled): ?>
        <form id="paychangu-premium-form" method="post" action="<?= $escape($baseUrl . '/payments/paychangu/premium') ?>">
          <?= \\App\\Core\\Csrf::field() ?>
          <button class="ps-modal-primary" type="submit"><i data-lucide="lock-keyhole" aria-hidden="true"></i> Upgrade securely with PayChangu</button>
        </form>
        <p class="ps-modal-footnote mt-3 mb-0" id="paychangu-checkout-status" role="status" aria-live="polite">Your payment is verified by InstaWeb before Premium is activated.</p>
        <div id="paychangu-dropin-panel" hidden class="mt-3"><div id="paychangu-checkout-container"></div></div>
      <?php else: ?>
        <button class="ps-modal-primary" type="button" disabled>Upgrades temporarily unavailable</button>
        <p class="ps-modal-footnote mt-3 mb-0">Premium checkout is not currently configured or enabled. You can review plan details on the <a href="<?= $escape($baseUrl . '/pricing') ?>">pricing page</a>.</p>
      <?php endif; ?>
      <p class="text-center small mt-3 mb-0"><a href="<?= $escape($baseUrl . '/pricing') ?>">Compare all plan features</a></p>
    </section>
  </div>
  <?php endif; ?>

  <div class="client-main">
    <header class="ps-heading">
      <div>
        <p class="ps-eyebrow">Portfolio Studio / Setup</p>
        <h1>Set up your portfolio</h1>
        <p>Add the essentials, pick a design and preview it live. Projects and detailed sections live in the Advanced Editor.</p>
      </div>
      <div class="ps-actions">
        <a class="btn btn-outline-primary" href="<?= $escape($baseUrl . '/dashboard/websites/show?id=' . $websiteId) ?>">Back to Website</a>
        <a class="btn btn-primary" href="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced?id=' . $websiteId) ?>"><i data-lucide="sliders-horizontal"></i> Advanced Editor</a>
      </div>
    </header>

    <!-- Step 1 -->
    <section class="ps-panel ps-profile" aria-labelledby="ps-profile-title">
      <div class="ps-panel-head">
        <div class="ps-title">
          <span class="ps-step" aria-hidden="true">1</span>
          <div>
            <h2 id="ps-profile-title">Your essentials</h2>
            <p>These appear at the top of every design.</p>
          </div>
        </div>
        <div class="ps-meter">
          <span class="ps-meter-text" id="ps-meter-text"><?= $doneCount ?> of 3 complete</span>
          <span class="ps-meter-bar" aria-hidden="true"><i id="ps-meter-fill" style="width:<?= (int) round($doneCount / 3 * 100) ?>%"></i></span>
          <span class="ps-status" id="portfolio-save-status" role="status" aria-live="polite">Changes save automatically</span>
        </div>
      </div>
      <div class="ps-profile-grid">
        <div class="ps-photo">
          <?php if ($avatarUrl !== ''): ?>
            <img class="ps-avatar" id="portfolio-avatar-preview" src="<?= $escape($avatarUrl) ?>" alt="Portfolio photo preview">
          <?php else: ?>
            <div class="ps-avatar ps-avatar-empty" id="portfolio-avatar-empty">Add a profile photo</div>
            <img class="ps-avatar" id="portfolio-avatar-preview" src="" alt="Portfolio photo preview" hidden>
          <?php endif; ?>
          <div>
            <form id="portfolio-avatar-form" method="POST" enctype="multipart/form-data" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/avatar') ?>">
              <?= \App\Core\Csrf::field() ?>
              <input type="hidden" name="id" value="<?= $websiteId ?>">
              <label class="btn btn-outline-primary btn-sm ps-photo-btn" for="avatar" id="ps-photo-label"><?= $avatarUrl !== '' ? 'Change photo' : 'Upload photo' ?></label>
              <input class="ps-visually-hidden" id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required>
              <noscript><button class="btn btn-primary btn-sm mt-2" type="submit">Save photo</button></noscript>
            </form>
            <p class="ps-hint mt-2">JPEG, PNG or WebP, up to 3 MB. Uploads automatically.</p>
          </div>
        </div>

        <form class="ps-fields" id="portfolio-setup-form" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/autosave') ?>" method="post">
          <?= \App\Core\Csrf::field() ?>
          <input type="hidden" name="id" value="<?= $websiteId ?>">
          <label class="ps-field" for="display_name">Full name
            <input id="display_name" name="display_name" maxlength="120" required autocomplete="name" value="<?= $escape($portfolio['display_name'] ?? '') ?>" placeholder="Your full name">
            <small>Shown as the main heading of your portfolio.</small>
          </label>
          <label class="ps-field" for="headline">Role / headline
            <input id="headline" name="headline" maxlength="180" value="<?= $escape($portfolio['headline'] ?? '') ?>" placeholder="e.g. UI Designer and Front-end Developer">
            <small>One short line that says what you do.</small>
          </label>
        </form>
      </div>
    </section>

    <div class="ps-layout">
      <!-- Step 2 -->
      <section class="ps-panel ps-gallery" aria-labelledby="ps-gallery-title">
        <div class="ps-panel-head">
          <div class="ps-title">
            <span class="ps-step" aria-hidden="true">2</span>
            <div>
              <h2 id="ps-gallery-title">Choose a design</h2>
              <p>Select a design to preview it. Your live site only changes when you press <strong>Confirm chosen template</strong>.</p>
            </div>
          </div>
          <span class="ps-active-control" id="portfolio-active-template">&#10003; Active: <?= $escape($currentShort) ?></span>
        </div>

        <div class="ps-toolbar">
          <label class="ps-search">
            <span class="ps-visually-hidden">Search designs</span>
            <input type="search" id="ps-search" placeholder="Search designs" autocomplete="off">
          </label>
          <div class="ps-chips" role="group" aria-label="Filter designs">
            <button type="button" class="ps-chip" data-filter="all" aria-pressed="true">All <b><?= $totalTemplates ?></b></button>
            <button type="button" class="ps-chip" data-filter="free" aria-pressed="false">Free <b><?= $freeCount ?></b></button>
            <button type="button" class="ps-chip" data-filter="premium" aria-pressed="false">Premium <b><?= $premiumCount ?></b></button>
          </div>
        </div>

        <div class="ps-template-grid" id="ps-template-grid" role="radiogroup" aria-label="Portfolio templates">
          <?php foreach ($orderedTemplates as $key => $label):
              $key = (string) $key;
              [$shortName, $labelTail] = $splitLabel((string) $label);
              $desc = $templateDescriptions[$key] ?? ($labelTail !== '' ? ucfirst($labelTail) : 'A distinctive portfolio design.');
              $premium = $isPremiumTemplate($key);
          ?>
            <label class="ps-template" data-tier="<?= $premium ? 'premium' : 'free' ?>" data-search="<?= $escape(strtolower($shortName . ' ' . $desc . ' ' . $key)) ?>">
              <input type="radio" name="template_choice" value="<?= $escape($key) ?>" data-premium="<?= $premium ? '1' : '0' ?>" form="portfolio-setup-form" <?= $currentTemplate === $key ? 'checked' : '' ?>>
              <span class="ps-mini" style="<?= $miniStyle($key) ?>" aria-hidden="true"><span></span><span></span><span></span></span>
              <span class="ps-tier ps-tier--<?= $premium ? 'premium' : 'free' ?>"><?= $premium ? 'Premium' : 'Free' ?></span>
              <span class="ps-template-check" aria-hidden="true">&#10003;</span>
              <span class="ps-template-name"><?= $escape($shortName) ?></span>
              <span class="ps-template-desc"><?= $escape($desc) ?></span>
              <span class="ps-template-active" data-active-template="<?= $escape($key) ?>" <?= $currentTemplate === $key ? '' : 'hidden' ?>>&#10003; Active</span>
            </label>
          <?php endforeach; ?>
        </div>
        <p class="ps-empty" id="ps-empty" hidden>No designs match your search. Try a different word or filter.</p>

        <div class="ps-more">
          <span class="ps-count" id="ps-count" aria-live="polite"></span>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="ps-toggle" hidden>Show all designs</button>
        </div>

        <div class="ps-next">
          <div>
            <strong>Ready to add your work?</strong>
            <span>Projects, experience, education and skills are added in the Advanced Editor.</span>
          </div>
          <a class="btn btn-outline-primary" href="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced?id=' . $websiteId) ?>">Edit projects and content</a>
        </div>
      </section>

      <!-- Live preview -->
      <aside class="ps-panel ps-preview-panel" aria-label="Template preview">
        <div class="ps-preview-head">
          <div>
            <h2>Live preview</h2>
            <p>Updates as you explore</p>
          </div>
          <div class="ps-device" role="group" aria-label="Preview size">
            <button type="button" data-device="desktop" aria-pressed="true">Desktop</button>
            <button type="button" data-device="mobile" aria-pressed="false">Mobile</button>
          </div>
        </div>

        <div class="ps-preview-now">
          <div>
            <span class="ps-label">Now previewing</span>
            <strong id="portfolio-preview-template-name"><?= $escape($currentShort) ?></strong>
          </div>
          <span class="ps-state" id="ps-preview-state" data-tone="active">Active</span>
        </div>

        <div class="ps-preview-stage" id="portfolio-preview-stage">
          <div class="ps-browser" aria-hidden="true"><i></i><i></i><i></i><span id="ps-device-label">Desktop &middot; 1440 px</span></div>
          <div class="ps-viewport" id="ps-viewport" data-device="desktop">
            <div class="ps-frame-wrap" id="ps-frame-wrap">
              <iframe class="ps-preview-frame" id="portfolio-template-preview" title="Preview of your selected portfolio template" src="<?= $escape($initialPreviewUrl) ?>" loading="lazy"></iframe>
              <div class="ps-loading" id="ps-loading" hidden>Loading preview&hellip;</div>
            </div>
          </div>
        </div>

        <div class="ps-preview-foot">
          <p class="ps-lock-note" id="ps-lock-note" hidden>This is a Premium design. You can preview it here, but you need Premium to use it on your live portfolio.</p>
          <button type="button" class="btn btn-primary ps-apply" id="portfolio-apply" disabled>Current template</button>
          <div class="ps-foot-links">
            <a id="portfolio-preview-open" href="<?= $escape($initialPreviewUrl) ?>" target="_blank" rel="noopener">Open this preview &#8599;</a>
            <a href="<?= $escape($ownPreviewUrl) ?>" target="_blank" rel="noopener">Open my full portfolio</a>
            <?php if ($isPremiumUser): ?>
              <a href="<?= $escape($ownPreviewUrl . '&export=pdf') ?>" target="_blank" rel="noopener">Export to PDF</a>
            <?php else: ?>
              <button type="button" data-upgrade-open>Unlock PDF export</button>
            <?php endif; ?>
          </div>
        </div>
        <p class="ps-preview-note">When an admin has configured a published sample for a design, the preview shows that example. Otherwise it shows your own draft.</p>
      </aside>
    </div>
  </div>
</section>

<script>
(function () {
  const form = document.getElementById('portfolio-setup-form');
  if (!form) return;

  const isPremiumUser = <?= $isPremiumUser ? 'true' : 'false' ?>;
  const ownPreviewUrl = <?= json_encode($ownPreviewUrl) ?>;
  const templatePreviewUrls = <?= json_encode($templatePreviewUrls, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  let activeTemplateKey = <?= json_encode($currentTemplate) ?>;

  const $ = id => document.getElementById(id);
  const status = $('portfolio-save-status');
  const toast = $('portfolio-toast');
  const toastMessage = $('portfolio-toast-message');
  const toastIcon = $('portfolio-toast-icon');
  const activeControl = $('portfolio-active-template');
  const previewName = $('portfolio-preview-template-name');
  const previewState = $('ps-preview-state');
  const lockNote = $('ps-lock-note');
  const applyButton = $('portfolio-apply');
  const preview = $('portfolio-template-preview');
  const previewOpen = $('portfolio-preview-open');
  const viewport = $('ps-viewport');
  const frameWrap = $('ps-frame-wrap');
  const loading = $('ps-loading');
  const deviceLabel = $('ps-device-label');
  const avatarInput = $('avatar');
  const avatarPreview = $('portfolio-avatar-preview');
  const avatarEmpty = $('portfolio-avatar-empty');
  const avatarForm = $('portfolio-avatar-form');
  const photoLabel = $('ps-photo-label');
  const meterText = $('ps-meter-text');
  const meterFill = $('ps-meter-fill');
  const radios = Array.from(document.querySelectorAll('input[name="template_choice"]'));

  /* ---------- toast ---------- */
  let toastTimer = null;
  function showToast(message, state) {
    if (!toast || !toastMessage) return;
    toastMessage.textContent = message;
    toast.dataset.state = state || 'success';
    if (toastIcon) toastIcon.textContent = state === 'error' ? '!' : '\u2713';
    toast.classList.add('is-visible');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('is-visible'), state === 'error' ? 5500 : 3200);
  }
  function setStatus(message, state) {
    status.textContent = message;
    status.dataset.state = state || '';
  }

  /* ---------- upgrade modal ---------- */
  const upgradeModal = $('portfolio-upgrade-modal');
  let upgradeReturnFocus = null;
  function openUpgradeModal() {
    if (!upgradeModal) return;
    upgradeReturnFocus = document.activeElement;
    upgradeModal.hidden = false;
    upgradeModal.setAttribute('aria-hidden', 'false');
    const closeButton = upgradeModal.querySelector('[data-upgrade-close]');
    if (closeButton) closeButton.focus();
  }
  function closeUpgradeModal() {
    if (!upgradeModal) return;
    upgradeModal.hidden = true;
    upgradeModal.setAttribute('aria-hidden', 'true');
    if (upgradeReturnFocus && typeof upgradeReturnFocus.focus === 'function' && document.contains(upgradeReturnFocus)) upgradeReturnFocus.focus();
  }
  document.querySelectorAll('[data-upgrade-open]').forEach(button => button.addEventListener('click', openUpgradeModal));

  /* PayChangu checkout modes. Payment success is always confirmed server-side. */
  const premiumForm = $('paychangu-premium-form');
  const paymentStatus = $('paychangu-checkout-status');
  const dropinPanel = $('paychangu-dropin-panel');
  const checkoutScriptPromises = {};
  function loadCheckoutScript(src, globalName) {
    if (globalName && window[globalName]) return Promise.resolve();
    if (checkoutScriptPromises[src]) return checkoutScriptPromises[src];
    checkoutScriptPromises[src] = new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = src;
      script.async = true;
      script.onload = resolve;
      script.onerror = () => reject(new Error('PayChangu checkout library could not be loaded.'));
      document.head.appendChild(script);
    });
    return checkoutScriptPromises[src];
  }
  if (premiumForm) premiumForm.addEventListener('submit', async event => {
    event.preventDefault();
    const button = premiumForm.querySelector('button[type="submit"]');
    const original = button ? button.innerHTML : '';
    if (button) { button.disabled = true; button.textContent = 'Preparing secure checkout…'; }
    if (paymentStatus) paymentStatus.textContent = 'Preparing your payment securely…';
    try {
      const response = await fetch(premiumForm.action, {
        method: 'POST', body: new FormData(premiumForm), credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      });
      const result = await response.json();
      if (!response.ok || !result.ok || !result.checkout) throw new Error(result.message || 'Unable to start checkout.');
      const c = result.checkout;
      if (c.checkout_mode === 'standard') {
        if (!c.checkout_url) throw new Error('PayChangu did not return a checkout link.');
        window.location.assign(c.checkout_url);
        return;
      }
      if (c.checkout_mode === 'inline') {
        await loadCheckoutScript('https://in.paychangu.com/js/popup.js', 'PaychanguCheckout');
        if (typeof window.PaychanguCheckout !== 'function') throw new Error('PayChangu Inline Checkout did not initialize.');
        if (paymentStatus) paymentStatus.textContent = 'Complete payment in the PayChangu popup. InstaWeb verifies the result before activating Premium.';
        window.PaychanguCheckout({
          public_key: c.public_key, tx_ref: c.tx_ref, amount: c.amount, currency: c.currency,
          callback_url: c.callback_url, return_url: c.return_url,
          customer: { email: c.email, first_name: c.first_name, last_name: c.last_name },
          customization: c.customization, meta: c.meta
        });
        return;
      }
      if (c.checkout_mode === 'dropin') {
        await loadCheckoutScript('https://checkout.paychangu.com/embed.js', 'PayChangu');
        if (!window.PayChangu || typeof window.PayChangu.mountCheckoutForm !== 'function') throw new Error('PayChangu Drop-in Checkout did not initialize.');
        if (dropinPanel) dropinPanel.hidden = false;
        if (paymentStatus) paymentStatus.textContent = 'Complete payment below. InstaWeb verifies the result before activating Premium.';
        window.PayChangu.mountCheckoutForm({
          target: '#paychangu-checkout-container', publicKey: c.public_key,
          formKey: 'instaweb-premium-upgrade', email: c.email, amount: c.amount,
          paymentReference: c.tx_ref, type: 'accordion',
          config: { name: 'InstaWeb Premium', currency: c.currency, show_amount: true, redirect_url: c.callback_url },
          onSuccess: () => { if (paymentStatus) paymentStatus.textContent = 'Payment submitted. Waiting for InstaWeb to verify the transaction…'; },
          onError: error => { if (paymentStatus) paymentStatus.textContent = error && error.message ? error.message : 'Checkout error. Premium has not been activated.'; }
        });
        return;
      }
      throw new Error('The configured checkout mode is not supported.');
    } catch (error) {
      if (paymentStatus) paymentStatus.textContent = error.message || 'Checkout could not be started. Please try again.';
      if (button) { button.disabled = false; button.innerHTML = original; }
    }
  });
  if (upgradeModal) {
    upgradeModal.querySelectorAll('[data-upgrade-close]').forEach(button => button.addEventListener('click', closeUpgradeModal));
    document.addEventListener('keydown', event => {
      if (upgradeModal.hidden) return;
      if (event.key === 'Escape') { closeUpgradeModal(); return; }
      if (event.key !== 'Tab') return;
      const focusable = Array.from(upgradeModal.querySelectorAll('a[href],button:not([disabled]),input:not([disabled])')).filter(el => el.offsetParent !== null);
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
  }

  /* ---------- helpers ---------- */
  const selectedRadio = () => radios.find(r => r.checked) || null;
  const cardName = radio => radio?.closest('.ps-template')?.querySelector('.ps-template-name')?.textContent?.trim() || radio?.value || '';
  const isLocked = radio => !isPremiumUser && !!radio && radio.dataset.premium === '1';

  function selectedPreviewUrl() {
    const selected = selectedRadio();
    if (!selected) return ownPreviewUrl;
    if (templatePreviewUrls[selected.value]) return templatePreviewUrls[selected.value];
    return ownPreviewUrl + '&template=' + encodeURIComponent(selected.value);
  }
  function reloadPreview() {
    if (!preview) return;
    const target = selectedPreviewUrl();
    if (loading) loading.hidden = false;
    preview.src = target + (target.includes('?') ? '&' : '?') + '_preview=' + Date.now();
    if (previewOpen) previewOpen.href = target;
  }
  if (preview) preview.addEventListener('load', () => { if (loading) loading.hidden = true; });

  /* ---------- UI state ---------- */
  function updateUi() {
    const selected = selectedRadio();
    const key = selected ? selected.value : activeTemplateKey;
    const isActive = key === activeTemplateKey;
    const locked = isLocked(selected) && !isActive;

    radios.forEach(radio => radio.closest('.ps-template')?.classList.toggle('is-selected', radio.checked));
    document.querySelectorAll('[data-active-template]').forEach(badge => { badge.hidden = badge.dataset.activeTemplate !== activeTemplateKey; });

    const activeRadio = radios.find(r => r.value === activeTemplateKey);
    if (activeControl) activeControl.textContent = '\u2713 Active: ' + (cardName(activeRadio) || activeTemplateKey);
    if (previewName) previewName.textContent = cardName(selected) || activeTemplateKey;

    if (previewState) {
      previewState.textContent = isActive ? 'Active' : (locked ? 'Premium preview' : 'Preview only');
      previewState.dataset.tone = isActive ? 'active' : (locked ? 'locked' : '');
    }
    if (lockNote) lockNote.hidden = !locked;
    if (applyButton) {
      if (isActive) { applyButton.textContent = 'Current template'; applyButton.disabled = true; }
      else if (locked) { applyButton.textContent = 'Unlock with Premium'; applyButton.disabled = false; }
      else { applyButton.textContent = 'Confirm chosen template'; applyButton.disabled = false; }
    }
  }

  function updateMeter() {
    let done = 0;
    if (form.display_name.value.trim()) done++;
    if (form.headline.value.trim()) done++;
    if (avatarPreview && !avatarPreview.hidden && avatarPreview.getAttribute('src')) done++;
    if (meterText) meterText.textContent = done + ' of 3 complete';
    if (meterFill) meterFill.style.width = Math.round(done / 3 * 100) + '%';
  }

  /* ---------- gallery filter ---------- */
  const grid = $('ps-template-grid');
  const cards = grid ? Array.from(grid.querySelectorAll('.ps-template')) : [];
  const searchInput = $('ps-search');
  const emptyState = $('ps-empty');
  const countLabel = $('ps-count');
  const toggleButton = $('ps-toggle');
  const chips = Array.from(document.querySelectorAll('.ps-chip'));
  const LIMIT = 9;
  let filter = 'all';
  let query = '';
  let expanded = false;

  function applyFilter() {
    const q = query.trim().toLowerCase();
    const matches = cards.filter(card => (filter === 'all' || card.dataset.tier === filter) && (!q || card.dataset.search.includes(q)));
    const defaultView = filter === 'all' && !q;
    const limit = defaultView && !expanded ? LIMIT : Infinity;
    cards.forEach(card => { card.hidden = true; });
    matches.slice(0, limit).forEach(card => { card.hidden = false; });
    if (emptyState) emptyState.hidden = matches.length > 0;
    if (toggleButton) {
      toggleButton.hidden = !(defaultView && matches.length > LIMIT);
      toggleButton.textContent = expanded ? 'Show fewer designs' : 'Show all ' + matches.length + ' designs';
    }
    if (countLabel) countLabel.textContent = 'Showing ' + Math.min(limit, matches.length) + ' of ' + cards.length + ' designs';
  }
  chips.forEach(chip => chip.addEventListener('click', () => {
    filter = chip.dataset.filter;
    chips.forEach(c => c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'));
    applyFilter();
  }));
  if (searchInput) searchInput.addEventListener('input', () => { query = searchInput.value; applyFilter(); });
  if (toggleButton) toggleButton.addEventListener('click', () => { expanded = !expanded; applyFilter(); });

  /* ---------- preview sizing and device toggle ---------- */
  const devices = {
    desktop: { w: 1440, h: 900, maxH: 0, label: 'Desktop \u00b7 1440 px' },
    mobile: { w: 390, h: 844, maxH: 540, label: 'Mobile \u00b7 390 px' }
  };
  let device = 'desktop';
  function fitPreview() {
    if (!viewport || !frameWrap || !preview) return;
    const d = devices[device];
    const available = viewport.clientWidth - (device === 'mobile' ? 32 : 0);
    if (available <= 0) return;
    let scale = available / d.w;
    if (d.maxH) scale = Math.min(scale, d.maxH / d.h);
    frameWrap.style.width = Math.round(d.w * scale) + 'px';
    frameWrap.style.height = Math.round(d.h * scale) + 'px';
    preview.style.width = d.w + 'px';
    preview.style.height = d.h + 'px';
    preview.style.transform = 'scale(' + scale + ')';
  }
  document.querySelectorAll('.ps-device button').forEach(button => button.addEventListener('click', () => {
    device = button.dataset.device;
    document.querySelectorAll('.ps-device button').forEach(b => b.setAttribute('aria-pressed', b === button ? 'true' : 'false'));
    if (viewport) viewport.dataset.device = device;
    if (deviceLabel) deviceLabel.textContent = devices[device].label;
    fitPreview();
  }));
  if (viewport && 'ResizeObserver' in window) new ResizeObserver(fitPreview).observe(viewport);
  else window.addEventListener('resize', fitPreview);

  /* ---------- saving ---------- */
  let timer = null;
  let requestNumber = 0;
  async function saveSetup(options) {
    if (!form.reportValidity()) return false;
    const templateToSave = (options && options.template) || activeTemplateKey;
    const currentRequest = ++requestNumber;
    setStatus('Saving\u2026', 'saving');
    try {
      const body = new FormData(form);
      body.set('template_key', templateToSave);
      body.set('template_choice', templateToSave);
      const response = await fetch(form.action, { method: 'POST', body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
      const result = await response.json();
      if (currentRequest !== requestNumber) return false;
      if (!response.ok || !result.ok) throw new Error(result.message || 'Could not save changes.');
      setStatus('All changes saved', 'saved');
      const changed = templateToSave !== activeTemplateKey;
      activeTemplateKey = templateToSave;
      updateUi();
      showToast(changed ? cardName(radios.find(r => r.value === templateToSave)) + ' is now your active template.' : 'Your changes were saved.', 'success');
      reloadPreview();
      return true;
    } catch (error) {
      if (currentRequest === requestNumber) {
        setStatus(error.message || 'Save failed. Check your connection.', 'error');
        showToast(error.message || 'Save failed. Check your connection and try again.', 'error');
      }
      return false;
    }
  }

  form.querySelectorAll('#display_name, #headline').forEach(input => {
    input.addEventListener('input', () => {
      updateMeter();
      setStatus('Unsaved changes\u2026', '');
      clearTimeout(timer);
      timer = setTimeout(() => saveSetup(), 650);
    });
    input.addEventListener('blur', () => { clearTimeout(timer); if (status.dataset.state !== 'saved') saveSetup(); });
  });

  radios.forEach(radio => radio.addEventListener('change', () => {
    updateUi();
    reloadPreview();
  }));

  if (applyButton) applyButton.addEventListener('click', async () => {
    const selected = selectedRadio();
    if (!selected) return;
    if (isLocked(selected) && selected.value !== activeTemplateKey) { openUpgradeModal(); return; }
    applyButton.disabled = true;
    applyButton.textContent = 'Saving\u2026';
    const ok = await saveSetup({ template: selected.value });
    if (!ok) updateUi();
  });

  /* ---------- avatar ---------- */
  let activeObjectUrl = null;
  async function uploadAvatar() {
    const file = avatarInput && avatarInput.files && avatarInput.files[0];
    if (!file) { showToast('Choose a profile photo first.', 'error'); return; }
    if (file.size > 3 * 1024 * 1024) {
      setStatus('Photo must be smaller than 3 MB.', 'error');
      showToast('Photo must be smaller than 3 MB.', 'error');
      avatarInput.value = '';
      return;
    }
    setStatus('Uploading photo\u2026', 'saving');
    try {
      const response = await fetch(avatarForm.action, { method: 'POST', body: new FormData(avatarForm), credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
      const result = await response.json().catch(() => ({ ok: false, message: 'Photo upload failed. Check the file and try again.' }));
      if (!response.ok || !result.ok) throw new Error(result.message || 'Photo upload failed.');
      if (result.avatar_url && avatarPreview) {
        avatarPreview.src = result.avatar_url + '?_=' + Date.now();
        avatarPreview.hidden = false;
        if (avatarEmpty) avatarEmpty.hidden = true;
      }
      avatarInput.value = '';
      if (photoLabel) photoLabel.textContent = 'Change photo';
      updateMeter();
      setStatus('Profile photo saved', 'saved');
      showToast('Profile photo saved.', 'success');
      reloadPreview();
    } catch (error) {
      const message = error.message || 'Photo upload failed. Check your connection.';
      setStatus(message, 'error');
      showToast(message, 'error');
    }
  }
  if (avatarInput) avatarInput.addEventListener('change', () => {
    const file = avatarInput.files && avatarInput.files[0];
    if (!file) return;
    if (activeObjectUrl) URL.revokeObjectURL(activeObjectUrl);
    activeObjectUrl = URL.createObjectURL(file);
    avatarPreview.src = activeObjectUrl;
    avatarPreview.hidden = false;
    if (avatarEmpty) avatarEmpty.hidden = true;
    uploadAvatar();
  });
  if (avatarForm) avatarForm.addEventListener('submit', event => { event.preventDefault(); uploadAvatar(); });

  /* ---------- init ---------- */
  updateUi();
  updateMeter();
  applyFilter();
  fitPreview();
})();
</script>
