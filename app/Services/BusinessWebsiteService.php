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
            'service_1_title'=>'Service One','service_1_text'=>'Describe your first core service and the result customers can expect.',
            'service_2_title'=>'Service Two','service_2_text'=>'Explain another service in plain language.',
            'service_3_title'=>'Service Three','service_3_text'=>'Highlight a specialist service or area where your business stands out.',
            'email'=>'','phone'=>'','address'=>'','cta_label'=>'Contact the business'
        ];
    }

    public function validate(array $input, string $fallbackName): array
    {
        $defaults=$this->defaults($fallbackName);
        $limits=['business_name'=>120,'tagline'=>180,'hero_title'=>180,'hero_description'=>1200,'about_title'=>180,'about_text'=>3000,'service_1_title'=>100,'service_1_text'=>700,'service_2_title'=>100,'service_2_text'=>700,'service_3_title'=>100,'service_3_text'=>700,'email'=>190,'phone'=>40,'address'=>240,'cta_label'=>60];
        $data=[];
        foreach($limits as $key=>$max){
            $value=trim((string)($input[$key]??$defaults[$key]));
            if($value==='' && in_array($key,['business_name','hero_title','hero_description'],true)) throw new \InvalidArgumentException('Business name, hero heading and introduction are required.');
            if(mb_strlen($value)>$max) throw new \InvalidArgumentException(ucwords(str_replace('_',' ',$key)).' is too long.');
            $data[$key]=$value;
        }
        if($data['email']!=='' && filter_var($data['email'],FILTER_VALIDATE_EMAIL)===false) throw new \InvalidArgumentException('Enter a valid business email address.');
        return $data;
    }

    public function writeDraft(int $userId,string $slug,array $data): void
    {
        $path=(new ProjectStorageService())->ensureProjectDirectory($userId,$slug);
        if(file_put_contents($path.DIRECTORY_SEPARATOR.'index.html',$this->render($data),LOCK_EX)===false) throw new \RuntimeException('The business website draft could not be updated.');
    }

    public function render(array $d): string
    {
        $e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $name=$e($d['business_name']??'Business');
        $services='';
        for($i=1;$i<=3;$i++) $services.='<article class="service"><small>0'.$i.'</small><h3>'.$e($d["service_{$i}_title"]??'').'</h3><p>'.nl2br($e($d["service_{$i}_text"]??'')).'</p></article>';
        $email=trim((string)($d['email']??''));$phone=trim((string)($d['phone']??''));
        $href=$email!==''?'mailto:'.$email:($phone!==''?'tel:'.preg_replace('/[^0-9+]/','',$phone):'#contact');
        $contact=$email!==''?$e($email):($phone!==''?$e($phone):'Contact us');
        $address=trim((string)($d['address']??''))!==''?'<p>'.nl2br($e($d['address'])).'</p>':'';
        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="'.$e($d['hero_description']??'').'"><title>'.$name.'</title><style>
:root{--ink:#17231f;--muted:#66736d;--green:#176b50;--dark:#10251f;--paper:#fbfaf7;--line:#e6e9e4}*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--paper);color:var(--ink);font:16px/1.65 Inter,system-ui,Arial,sans-serif}.wrap{width:min(1100px,calc(100% - 40px));margin:auto}a{color:inherit}.top{background:var(--dark);color:#e5eee8;font-size:13px;padding:8px 0}.top .wrap,nav,.links,.actions{display:flex;align-items:center;justify-content:space-between;gap:22px}header{position:sticky;top:0;background:#fbfaf7ed;border-bottom:1px solid var(--line);z-index:2}.nav{min-height:76px}.brand{font-weight:850;font-size:21px;text-decoration:none}.links a{text-decoration:none;color:#526159;font-size:14px;font-weight:650}.button{display:inline-flex;padding:12px 19px;background:var(--green);color:#fff;text-decoration:none;border-radius:7px;font-weight:750;font-size:14px}.hero{padding:95px 0;background:radial-gradient(ellipse at 85% 20%,#e2ecdf,transparent 38%)}.hero h1{font-size:clamp(42px,7vw,72px);line-height:1.04;letter-spacing:-3px;max-width:850px;margin:18px 0}.hero p{max-width:650px;color:var(--muted);font-size:18px}.eyebrow{color:var(--green);font-size:12px;font-weight:850;letter-spacing:1.6px;text-transform:uppercase}.actions{justify-content:flex-start;margin-top:28px}.section{padding:78px 0}.section h2{font-size:clamp(30px,4vw,44px);letter-spacing:-1.5px;line-height:1.12;max-width:700px}.muted{color:var(--muted);max-width:650px}.cards{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:35px}.service{padding:27px;background:#fff;border:1px solid var(--line);border-radius:12px}.service small{color:#bd9250;font-weight:850}.service p{color:var(--muted);font-size:14px}.about{background:#e8f1eb}.contact{background:var(--dark);color:#fff}.contact p{color:#cbd8d0}.footer{padding:30px 0;color:var(--muted);font-size:13px;border-top:1px solid var(--line)}@media(max-width:700px){.wrap{width:calc(100% - 32px)}.nav{align-items:flex-start;flex-direction:column;padding:15px 0}.links{flex-wrap:wrap;gap:14px}.hero{padding:60px 0}.cards{grid-template-columns:1fr}.section{padding:55px 0}.hero h1{letter-spacing:-2px}}</style></head><body>
<div class="top"><div class="wrap"><span>'.$e($d['tagline']??'').'</span><a href="#contact">Get in touch ↗</a></div></div><header><nav class="wrap nav"><a class="brand" href="#">'.$name.'</a><div class="links"><a href="#services">Services</a><a href="#about">About</a><a href="#contact">Contact</a></div><a class="button" href="#contact">Let’s talk</a></nav></header>
<main><section class="hero"><div class="wrap"><div class="eyebrow">Welcome to '.$name.'</div><h1>'.nl2br($e($d['hero_title']??'')).'</h1><p>'.nl2br($e($d['hero_description']??'')).'</p><div class="actions"><a class="button" href="#contact">'.$e($d['cta_label']??'Contact us').' ↗</a><a href="#services">Explore services</a></div></div></section>
<section class="section" id="services"><div class="wrap"><div class="eyebrow">What we do</div><h2>Services that make a difference.</h2><div class="cards">'.$services.'</div></div></section>
<section class="section about" id="about"><div class="wrap"><div class="eyebrow">About us</div><h2>'.$e($d['about_title']??'').'</h2><p class="muted">'.nl2br($e($d['about_text']??'')).'</p></div></section>
<section class="section contact" id="contact"><div class="wrap"><div class="eyebrow">Get in touch</div><h2>Let’s discuss how we can help.</h2><p>'.$contact.'</p>'.$address.'<a class="button" href="'.$e($href).'">'.$e($d['cta_label']??'Contact us').' ↗</a></div></section></main>
<footer class="footer"><div class="wrap">© '.date('Y').' '.$name.'. All rights reserved.</div></footer></body></html>';
    }
}
