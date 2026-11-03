<?php
$baseUrl=rtrim((string)($app['base_url']??''),'/');
$e=static fn($v)=>htmlspecialchars((string)($v??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$groups=[
'Business identity'=>[['business_name','Business name','text'],['tagline','Short tagline','text']],
'Hero section'=>[['hero_title','Hero heading','text'],['hero_description','Introduction','textarea']],
'About section'=>[['about_title','About heading','text'],['about_text','About your business','textarea']],
'Services'=>[['service_1_title','Service 1 title','text'],['service_1_text','Service 1 description','textarea'],['service_2_title','Service 2 title','text'],['service_2_text','Service 2 description','textarea'],['service_3_title','Service 3 title','text'],['service_3_text','Service 3 description','textarea']],
'Contact details'=>[['email','Public email','email'],['phone','Phone / WhatsApp','text'],['address','Business address','textarea'],['cta_label','Contact button label','text']]
];
?>
<style>
.business-editor{max-width:1050px;margin:auto;color:#17231f}.business-editor .be-head{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1.3rem}.business-editor h1{font-size:clamp(1.7rem,3vw,2.25rem);font-weight:800;letter-spacing:-.04em;margin:0}.business-editor .be-muted{color:#66736d;font-size:.9rem}.business-editor .be-card{background:#fff;border:1px solid #e5e9e5;border-radius:14px;padding:clamp(1rem,3vw,1.5rem);margin-bottom:1rem}.business-editor .be-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.business-editor label{display:grid;gap:.4rem;font-size:.85rem;font-weight:700}.business-editor input,.business-editor textarea{width:100%;padding:.75rem .85rem;border:1px solid #d5ddd7;border-radius:8px;font:inherit;font-weight:400;color:#17231f;background:#fff}.business-editor textarea{min-height:110px;resize:vertical}.business-editor .wide{grid-column:1/-1}.business-editor .be-actions{display:flex;flex-wrap:wrap;gap:.7rem;justify-content:flex-end}@media(max-width:650px){.business-editor .be-grid{grid-template-columns:1fr}.business-editor .be-head{flex-direction:column}.business-editor .be-actions>*{width:100%}}
</style>
<section class="client-dashboard iw-client-page business-editor">
<header class="be-head"><div><p class="text-uppercase fw-bold small text-muted mb-2">Business website / Customizer</p><h1>Customize your website</h1><p class="be-muted mb-0"><?= $e($website['website_name']) ?> · Changes update your private draft.</p></div><div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-secondary" href="<?= $e($baseUrl.'/dashboard/websites/show?id='.(int)$website['id']) ?>">Manage site</a><a class="btn btn-outline-primary" href="<?= $e($baseUrl.'/dashboard/websites/preview?id='.(int)$website['id']) ?>" target="_blank" rel="noopener">Preview</a></div></header>
<form method="POST" action="<?= $e($baseUrl.'/dashboard/websites/business/save') ?>">
<?= \\App\\Core\\Csrf::field() ?><input type="hidden" name="id" value="<?= (int)$website['id'] ?>">
<?php foreach($groups as $heading=>$fields): ?><section class="be-card"><h2 class="h5 fw-bold mb-3"><?= $e($heading) ?></h2><div class="be-grid">
<?php foreach($fields as [$key,$label,$type]): ?><label class="<?= $type==='textarea'?'wide':'' ?>"><?= $e($label) ?><?php if($type==='textarea'): ?><textarea name="<?= $e($key) ?>" maxlength="<?= $key==='about_text'?3000:($key==='hero_description'?1200:($key==='address'?240:700)) ?>"><?= $e($content[$key]??'') ?></textarea><?php else: ?><input type="<?= $e($type) ?>" name="<?= $e($key) ?>" maxlength="<?= $key==='business_name'?120:($key==='email'?190:180) ?>" value="<?= $e($content[$key]??'') ?>" <?= in_array($key,['business_name','hero_title','hero_description'],true)?'required':'' ?>><?php endif; ?></label><?php endforeach; ?>
</div></section><?php endforeach; ?>
<div class="be-actions mb-4"><a class="btn btn-light" href="<?= $e($baseUrl.'/dashboard/websites/show?id='.(int)$website['id']) ?>">Cancel</a><button class="btn btn-primary" type="submit">Save website content</button></div>
</form></section>
