<?php
namespace App\Services;

/** Renders structured content for managed Business websites. */
class BusinessWebsiteService
{
    public function defaults(string $name): array
    {
        return [
            'business_name'=>$name,'tagline'=>'Professional service. Personal attention.',
            'hero_title'=>'Good work. Built around you.','hero_description'=>'We help people and businesses move forward with reliable services and thoughtful solutions.',
            'about_title'=>'A business built on doing things well.','about_text'=>'Introduce your business, its experience and the people behind it.',
            'services'=>[
                ['title'=>'Service One','text'=>'Describe your first core service and the result customers can expect.'],
                ['title'=>'Service Two','text'=>'Explain another service in plain language.'],
                ['title'=>'Service Three','text'=>'Highlight a specialist service or area where your business stands out.'],
            ],
            'email'=>'','phone'=>'','address'=>'','cta_label'=>'Contact the business','hero_image_path'=>'',
            'section_visibility'=>['hero'=>true,'services'=>true,'about'=>true,'contact'=>true]
        ];
    }

    public function validate(array $input, string $fallbackName): array
    {
        $defaults=$this->defaults($fallbackName);
        $limits=['business_name'=>120,'tagline'=>180,'hero_title'=>180,'hero_description'=>1200,'about_title'=>180,'about_text'=>3000,'email'=>190,'phone'=>40,'address'=>240,'cta_label'=>60,'hero_image_path'=>100];
        $data=[];
        foreach($limits as $key=>$max){
            $value=trim((string)($input[$key]??$defaults[$key]));
            if($value==='' && in_array($key,['business_name','hero_title','hero_description'],true)) throw new \InvalidArgumentException('Business name, hero heading and introduction are required.');
            if(mb_strlen($value)>$max) throw new \InvalidArgumentException(ucwords(str_replace('_',' ',$key)).' is too long.');
            $data[$key]=$value;
        }
        $submittedServices = array_key_exists('services_submitted', $input) ? ($input['services'] ?? []) : ($input['services'] ?? $defaults['services']);
        if (!is_array($submittedServices)) {
            throw new \InvalidArgumentException('Services must be submitted as a list.');
        }
        if (count($submittedServices) > 12) {
            throw new \InvalidArgumentException('You can add up to 12 services.');
        }
        $data['services'] = [];
        foreach (array_values($submittedServices) as $service) {
            if (!is_array($service)) continue;
            $title = trim((string) ($service['title'] ?? ''));
            $description = trim((string) ($service['text'] ?? ''));
            if ($title === '' && $description === '') continue;
            if ($title === '' || $description === '') {
                throw new \InvalidArgumentException('Each service needs both a title and a description.');
            }
            if (mb_strlen($title) > 100 || mb_strlen($description) > 700) {
                throw new \InvalidArgumentException('Service titles must be 100 characters or fewer and descriptions 700 characters or fewer.');
            }
            $data['services'][] = ['title' => $title, 'text' => $description];
        }
        $heroImage = trim((string) ($input['hero_image_path'] ?? $defaults['hero_image_path']));
        if ($heroImage !== '' && !preg_match('~^assets/media/media-[a-f0-9]{24}\.(?:jpg|png|webp)$~', $heroImage)) {
            throw new \InvalidArgumentException('Choose a valid image from your media library.');
        }
        $data['hero_image_path'] = $heroImage;
        $visibilityInput = $input['section_visibility'] ?? [];
        if (!is_array($visibilityInput)) {
            $visibilityInput = [];
        }
        $data['section_visibility'] = [];
        foreach (['hero', 'services', 'about', 'contact'] as $section) {
            $value = $visibilityInput[$section] ?? '1';
            $data['section_visibility'][$section] = in_array($value, [true, 1, '1', 'true', 'on'], true);
        }
        if($data['email']!=='' && filter_var($data['email'],FILTER_VALIDATE_EMAIL)===false) throw new \InvalidArgumentException('Enter a valid business email address.');
        return $data;
    }

    public function writeDraft(int $userId,string $slug,array $data): void
    {
        $path=(new ProjectStorageService())->ensureProjectDirectory($userId,$slug);
        if(file_put_contents($path.DIRECTORY_SEPARATOR.'index.html',$this->render($data),LOCK_EX)===false) throw new \RuntimeException('The business website draft could not be updated.');
    }

    public function render(array $d, array $branding = []): string
    {
        $e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $name=$e($d['business_name']??'Business');
        $logoPath = (string) ($branding['logo_path'] ?? '');
        $logoValid = $logoPath !== '' && preg_match('~^assets/branding/(?:logo|favicon)-[a-f0-9]{24}\\.(?:jpg|png|webp)$~', $logoPath);
        $logoUrl = $logoValid ? (string) ($branding['logo_url'] ?? $logoPath) : '';
        $logo = $logoUrl !== ''
            ? '<img src="'.$e($logoUrl).'" alt="'.$name.'" style="max-height:42px;max-width:180px;object-fit:contain">'
            : $name;
        $faviconPath = (string) ($branding['favicon_path'] ?? '');
        $faviconValid = $faviconPath !== '' && preg_match('~^assets/branding/favicon-[a-f0-9]{24}\\.(?:jpg|png|webp)$~', $faviconPath);
        $faviconUrl = $faviconValid ? (string) ($branding['favicon_url'] ?? $faviconPath) : '';
        $favicon = $faviconUrl !== '' ? '<link rel="icon" href="'.$e($faviconUrl).'">' : '';
        $serviceItems = $d['services'] ?? [];
        if (!is_array($serviceItems) || $serviceItems === []) {
            $serviceItems = [];
            for ($i = 1; $i <= 3; $i++) {
                $serviceItems[] = ['title' => $d["service_{$i}_title"] ?? '', 'text' => $d["service_{$i}_text"] ?? ''];
            }
        }
        $services = '';
        foreach (array_values($serviceItems) as $index => $item) {
            if (!is_array($item)) continue;
            $services .= '<article class="service"><small>' . sprintf('%02d', $index + 1) . '</small><h3>' . $e($item['title'] ?? '') . '</h3><p>' . nl2br($e($item['text'] ?? '')) . '</p></article>';
        }
        $email=trim((string)($d['email']??''));$phone=trim((string)($d['phone']??''));
        $href=$email!==''?'mailto:'.$email:($phone!==''?'tel:'.preg_replace('/[^0-9+]/','',$phone):'#contact');
        $contactParts=[];if($email!=='') $contactParts[]='<a href="mailto:'.$e($email).'">'.$e($email).'</a>';if($phone!=='') $contactParts[]='<a href="tel:'.preg_replace('/[^0-9+]/','',$phone).'">'.$e($phone).'</a>';$contact=$contactParts!==[]?implode(' · ',$contactParts):'Contact us';
        $address=trim((string)($d['address']??''))!==''?'<p>'.nl2br($e($d['address'])).'</p>':'';
        $visibility = is_array($d['section_visibility'] ?? null)
            ? $d['section_visibility']
            : ['hero' => true, 'services' => true, 'about' => true, 'contact' => true];
        foreach (['hero', 'services', 'about', 'contact'] as $sectionKey) {
            $visibility[$sectionKey] = !array_key_exists($sectionKey, $visibility)
                ? true
                : in_array($visibility[$sectionKey], [true, 1, '1', 'true', 'on'], true);
        }
        $heroImagePath = (string) ($d['hero_image_path'] ?? '');
        $heroImage = preg_match('~^assets/media/media-[a-f0-9]{24}\.(?:jpg|png|webp)$~', $heroImagePath)
            ? '<div class="hero-image"><img src="'.$e($d['hero_image_url'] ?? $heroImagePath).'" alt="'.$e($d['business_name'] ?? 'Business').'" loading="eager"></div>'
            : '';
        $hasContactTarget = $visibility['contact'];
        $heroContactHref = $hasContactTarget ? '#contact' : ($email !== '' ? 'mailto:' . $email : ($phone !== '' ? 'tel:' . preg_replace('/[^0-9+]/', '', $phone) : ''));
        $topContact = $hasContactTarget ? '<a href="#contact">Get in touch ↗</a>' : '';
        $navLinks = '';
        if ($visibility['services']) $navLinks .= '<a href="#services">Services</a>';
        if ($visibility['about']) $navLinks .= '<a href="#about">About</a>';
        if ($visibility['contact']) $navLinks .= '<a href="#contact">Contact</a>';
        $headerContact = $hasContactTarget ? '<a class="button" href="#contact">Let’s talk</a>' : '';
        $heroSection = $visibility['hero']
            ? '<section class="hero"><div class="wrap">'.$heroImage.'<div class="eyebrow">Welcome to '.$name.'</div><h1>'.nl2br($e($d['hero_title']??'')).'</h1><p>'.nl2br($e($d['hero_description']??'')).'</p><div class="actions">'.($heroContactHref !== '' ? '<a class="button" href="'.$e($heroContactHref).'">'.$e($d['cta_label']??'Contact us').' ↗</a>' : '').($visibility['services'] ? '<a href="#services">Explore services</a>' : '').'</div></div></section>'
            : '';
        $servicesSection = $visibility['services']
            ? '<section class="section" id="services"><div class="wrap"><div class="eyebrow">What we do</div><h2>Services that make a difference.</h2><div class="cards">'.$services.'</div></div></section>'
            : '';
        $aboutSection = $visibility['about']
            ? '<section class="section about" id="about"><div class="wrap"><div class="eyebrow">About us</div><h2>'.$e($d['about_title']??'').'</h2><p class="muted">'.nl2br($e($d['about_text']??'')).'</p></div></section>'
            : '';
        $contactSection = $visibility['contact']
            ? '<section class="section contact" id="contact"><div class="wrap"><div class="eyebrow">Get in touch</div><h2>Let’s discuss how we can help.</h2><p>'.$contact.'</p>'.$address.'<a class="button" href="'.$e($href).'">'.$e($d['cta_label']??'Contact us').' ↗</a></div></section>'
            : '';

        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="'.$e($d['hero_description']??'').'"><title>'.$name.'</title>'.$favicon.'<style>
:root{--ink:#17231f;--muted:#66736d;--green:#176b50;--dark:#10251f;--paper:#fbfaf7;--line:#e6e9e4}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--paper);color:var(--ink);font:16px/1.65 Inter,system-ui,Arial,sans-serif}.wrap{width:min(1100px,calc(100% - 40px));margin:auto}a{color:inherit}.top{background:var(--dark);color:#e5eee8;font-size:13px;padding:8px 0}.top .wrap,nav,.links,.actions{display:flex;align-items:center;justify-content:space-between;gap:22px}header{position:sticky;top:0;background:#fbfaf7ed;border-bottom:1px solid var(--line);z-index:2}.nav{min-height:76px}.brand{font-weight:850;font-size:21px;text-decoration:none}.links a{text-decoration:none;color:#526159;font-size:14px;font-weight:650}.button{display:inline-flex;padding:12px 19px;background:var(--green);color:#fff;text-decoration:none;border-radius:7px;font-weight:750;font-size:14px}.hero-image{margin:0 0 30px;max-width:760px}.hero-image img{display:block;width:100%;max-height:380px;object-fit:cover;border-radius:14px}.hero{padding:95px 0;background:radial-gradient(ellipse at 85% 20%,#e2ecdf,transparent 38%)}.hero h1{font-size:clamp(42px,7vw,72px);line-height:1.04;letter-spacing:-3px;max-width:850px;margin:18px 0}.hero p{max-width:650px;color:var(--muted);font-size:18px}.eyebrow{color:var(--green);font-size:12px;font-weight:850;letter-spacing:1.6px;text-transform:uppercase}.actions{justify-content:flex-start;margin-top:28px}.section{padding:78px 0}.section h2{font-size:clamp(30px,4vw,44px);letter-spacing:-1.5px;line-height:1.12;max-width:700px}.muted{color:var(--muted);max-width:650px}.cards{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:35px}.service{padding:27px;background:#fff;border:1px solid var(--line);border-radius:12px}.service small{color:#bd9250;font-weight:850}.service p{color:var(--muted);font-size:14px}.about{background:#e8f1eb}.contact{background:var(--dark);color:#fff}.contact p{color:#cbd8d0}.footer{padding:30px 0;color:var(--muted);font-size:13px;border-top:1px solid var(--line)}@media(max-width:700px){.wrap{width:calc(100% - 32px)}.nav{align-items:flex-start;flex-direction:column;padding:15px 0}.links{flex-wrap:wrap;gap:14px}.hero{padding:60px 0}.cards{grid-template-columns:1fr}.section{padding:55px 0}.hero h1{letter-spacing:-2px}}</style></head><body>
<div class="top"><div class="wrap"><span>'.$e($d['tagline']??'').'</span>'.$topContact.'</div></div><header><nav class="wrap nav"><a class="brand" href="#">'.$logo.'</a><div class="links">'.$navLinks.'</div>'.$headerContact.'</nav></header>
<main>'.$heroSection.$servicesSection.$aboutSection.$contactSection.'</main>
<footer class="footer"><div class="wrap">© '.date('Y').' '.$name.'. All rights reserved.</div></footer></body></html>';
    }
}
