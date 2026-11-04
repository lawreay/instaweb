<?php
$editorActive = 'content';
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$e = static fn ($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$groups = [
    'Business identity' => [['business_name', 'Business name', 'text'], ['tagline', 'Short tagline', 'text']],
    'Hero section' => [['hero_title', 'Hero heading', 'text'], ['hero_description', 'Introduction', 'textarea']],
    'About section' => [['about_title', 'About heading', 'text'], ['about_text', 'About your business', 'textarea']],
    'Contact details' => [['email', 'Public email', 'email'], ['phone', 'Phone / WhatsApp', 'text'], ['address', 'Business address', 'textarea'], ['cta_label', 'Contact button label', 'text']],
];
$services = $content['services'] ?? null;
if (!is_array($services)) {
    $services = [];
    for ($i = 1; $i <= 3; $i++) {
        $services[] = [
            'title' => $content["service_{$i}_title"] ?? '',
            'text' => $content["service_{$i}_text"] ?? '',
        ];
    }
}
$services = array_values($services);
?>
<style>
.business-editor{max-width:1050px;margin:auto;color:#17231f}.business-editor .be-head{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1.3rem}.business-editor h1{font-size:clamp(1.7rem,3vw,2.25rem);font-weight:800;letter-spacing:-.04em;margin:0}.business-editor .be-muted{color:#66736d;font-size:.9rem}.business-editor .be-card{background:#fff;border:1px solid #e5e9e5;border-radius:14px;padding:clamp(1rem,3vw,1.5rem);margin-bottom:1rem}.business-editor .be-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.business-editor label{display:grid;gap:.4rem;font-size:.85rem;font-weight:700}.business-editor input,.business-editor textarea{width:100%;padding:.75rem .85rem;border:1px solid #d5ddd7;border-radius:8px;font:inherit;font-weight:400;color:#17231f;background:#fff}.business-editor textarea{min-height:110px;resize:vertical}.business-editor .wide{grid-column:1/-1}.business-editor .be-actions{display:flex;flex-wrap:wrap;gap:.7rem;justify-content:flex-end}.business-editor .service-item{border:1px solid #e2e8e3;border-radius:10px;padding:1rem;margin-top:.8rem;background:#fbfcfb}.business-editor .service-item-head{display:flex;justify-content:space-between;align-items:center;gap:.7rem;margin-bottom:.8rem}.business-editor .service-item-actions{display:flex;gap:.35rem}.business-editor .service-item-actions button{border:1px solid #d5ddd7;background:#fff;border-radius:6px;padding:.35rem .6rem;font-size:.8rem}.business-editor .service-item-actions button:disabled{opacity:.4;cursor:not-allowed}.business-editor .service-empty{padding:1rem;border:1px dashed #cbd5ce;border-radius:9px;color:#66736d;text-align:center}.business-editor .service-tools{display:flex;justify-content:space-between;align-items:center;gap:.8rem;flex-wrap:wrap}.business-editor .be-help{font-size:.8rem;color:#66736d}@media(max-width:650px){.business-editor .be-grid{grid-template-columns:1fr}.business-editor .be-head{flex-direction:column}.business-editor .be-actions>*{width:100%}}
</style>
<section class="client-dashboard iw-client-page business-editor">
<?php require __DIR__ . '/partials/editor-navigation.php'; ?>
<header class="be-head"><div><p class="text-uppercase fw-bold small text-muted mb-2">Business website / Customizer</p><h1>Customize your website</h1><p class="be-muted mb-0"><?= $e($website['website_name']) ?> · Changes update your private draft.</p></div><div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-secondary" href="<?= $e($baseUrl.'/dashboard/websites/show?id='.(int)$website['id']) ?>">Manage site</a><a class="btn btn-outline-primary" href="<?= $e($baseUrl.'/dashboard/websites/preview?id='.(int)$website['id']) ?>" target="_blank" rel="noopener">Preview</a></div></header>
<form method="POST" action="<?= $e($baseUrl.'/dashboard/websites/business/save') ?>">
<?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$website['id'] ?>"><input type="hidden" name="services_submitted" value="1">
<?php foreach ($groups as $heading => $fields): ?><section class="be-card"><h2 class="h5 fw-bold mb-3"><?= $e($heading) ?></h2><div class="be-grid">
<?php foreach ($fields as [$key, $label, $type]): ?><label class="<?= $type === 'textarea' ? 'wide' : '' ?>"><?= $e($label) ?><?php if ($type === 'textarea'): ?><textarea name="<?= $e($key) ?>" maxlength="<?= $key === 'about_text' ? 3000 : ($key === 'hero_description' ? 1200 : 240) ?>"><?= $e($content[$key] ?? '') ?></textarea><?php else: ?><input type="<?= $e($type) ?>" name="<?= $e($key) ?>" maxlength="<?= $key === 'business_name' ? 120 : ($key === 'email' ? 190 : 180) ?>" value="<?= $e($content[$key] ?? '') ?>" <?= in_array($key, ['business_name', 'hero_title', 'hero_description'], true) ? 'required' : '' ?>><?php endif; ?></label><?php endforeach; ?>
</div></section><?php endforeach; ?>
<section class="be-card" id="section-visibility">
    <h2 class="h5 fw-bold mb-1">Section visibility</h2>
    <p class="be-help mb-3">Choose which sections appear on your published website. Hiding a section does not delete its content.</p>
    <div class="be-grid">
        <?php foreach (['hero' => 'Hero section', 'services' => 'Services section', 'about' => 'About section', 'contact' => 'Contact section'] as $sectionKey => $sectionLabel): ?>
        <label class="d-flex align-items-center gap-2"><input type="hidden" name="section_visibility[<?= $e($sectionKey) ?>]" value="0"><input type="checkbox" name="section_visibility[<?= $e($sectionKey) ?>]" value="1" <?= !isset($content['section_visibility'][$sectionKey]) || !empty($content['section_visibility'][$sectionKey]) ? 'checked' : '' ?>> <?= $e($sectionLabel) ?></label>
        <?php endforeach; ?>
    </div>
</section>
<section class="be-card" id="service-manager">
    <div class="service-tools"><div><h2 class="h5 fw-bold mb-1">Services</h2><p class="be-help mb-0">Add up to 12 services. Use the arrows to change their order.</p></div><button class="btn btn-outline-primary btn-sm" type="button" id="add-service">+ Add service</button></div>
    <div id="service-list">
        <?php foreach ($services as $index => $service): ?>
        <article class="service-item" data-service-item>
            <div class="service-item-head"><strong data-service-number>Service <?= $index + 1 ?></strong><div class="service-item-actions"><button type="button" data-move-up aria-label="Move service up">↑</button><button type="button" data-move-down aria-label="Move service down">↓</button><button type="button" data-remove-service>Remove</button></div></div>
            <div class="be-grid"><label>Service title<input type="text" name="services[<?= $index ?>][title]" maxlength="100" value="<?= $e($service['title'] ?? '') ?>" required></label><label class="wide">Description<textarea name="services[<?= $index ?>][text]" maxlength="700" required><?= $e($service['text'] ?? '') ?></textarea></label></div>
        </article>
        <?php endforeach; ?>
    </div>
    <p class="service-empty" id="service-empty" <?= $services !== [] ? 'hidden' : '' ?>>No services added yet. Add one when your business is ready to list its offerings.</p>
</section>
<div class="be-actions mb-4"><a class="btn btn-light" href="<?= $e($baseUrl.'/dashboard/websites/show?id='.(int)$website['id']) ?>">Cancel</a><button class="btn btn-primary" type="submit">Save website content</button></div>
</form></section>
<script>
(function () {
    const list = document.getElementById('service-list');
    const add = document.getElementById('add-service');
    const empty = document.getElementById('service-empty');
    if (!list || !add || !empty) return;
    const max = 12;
    function refresh() {
        const items = Array.from(list.querySelectorAll('[data-service-item]'));
        items.forEach((item, index) => {
            item.querySelector('[data-service-number]').textContent = 'Service ' + (index + 1);
            item.querySelector('[data-move-up]').disabled = index === 0;
            item.querySelector('[data-move-down]').disabled = index === items.length - 1;
            item.querySelectorAll('input, textarea').forEach((field) => {
                const key = field.name.endsWith('[title]') ? 'title' : 'text';
                field.name = 'services[' + index + '][' + key + ']';
            });
        });
        empty.hidden = items.length > 0;
        add.disabled = items.length >= max;
        add.textContent = items.length >= max ? 'Maximum reached' : '+ Add service';
    }
    add.addEventListener('click', () => {
        const items = list.querySelectorAll('[data-service-item]');
        if (items.length >= max) return;
        const item = document.createElement('article');
        item.className = 'service-item';
        item.setAttribute('data-service-item', '');
        item.innerHTML = '<div class="service-item-head"><strong data-service-number></strong><div class="service-item-actions"><button type="button" data-move-up aria-label="Move service up">↑</button><button type="button" data-move-down aria-label="Move service down">↓</button><button type="button" data-remove-service>Remove</button></div></div><div class="be-grid"><label>Service title<input type="text" maxlength="100" required></label><label class="wide">Description<textarea maxlength="700" required></textarea></label></div>';
        list.appendChild(item);
        refresh();
        item.querySelector('input').focus();
    });
    list.addEventListener('click', (event) => {
        const item = event.target.closest('[data-service-item]');
        if (!item) return;
        if (event.target.closest('[data-remove-service]')) item.remove();
        if (event.target.closest('[data-move-up]') && item.previousElementSibling) list.insertBefore(item, item.previousElementSibling);
        if (event.target.closest('[data-move-down]') && item.nextElementSibling) list.insertBefore(item.nextElementSibling, item);
        refresh();
    });
    refresh();
})();
</script>
