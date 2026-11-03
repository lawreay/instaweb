<?php

namespace App\Services;

/**
 * Builds and manages safe draft storage paths for website projects.
 */
class ProjectStorageService
{
    /**
     * Return the root folder for all draft projects.
     */
    public function projectsRoot(): string
    {
        return STORAGE_PATH . DIRECTORY_SEPARATOR . 'projects';
    }

    /**
     * Return a draft project directory from trusted user and slug values.
     */
    public function projectPath(int $userId, string $slug): string
    {
        return $this->projectsRoot() . DIRECTORY_SEPARATOR . $userId . DIRECTORY_SEPARATOR . $slug;
    }

    /**
     * Resolve a user-supplied relative path safely inside a project.
     */
    public function resolveProjectPath(int $userId, string $slug, string $relativePath = ''): string
    {
        $projectPath = $this->ensureProjectDirectory($userId, $slug);
        $safeRelativePath = $this->normalizeRelativePath($relativePath);

        return $safeRelativePath === ''
            ? $projectPath
            : $projectPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safeRelativePath);
    }

    /**
     * Create the draft project directory if needed.
     */
    public function ensureProjectDirectory(int $userId, string $slug): string
    {
        $path = $this->projectPath($userId, $slug);

        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException('Unable to create project folder.');
        }

        return $path;
    }

    /**
     * Create the starter index file for a new project.
     */
    public function createStarterIndex(string $projectPath, string $websiteName, string $template): int
    {
        $templateLabel = $this->templateLabel($template);
        $safeTitle = htmlspecialchars($websiteName, ENT_QUOTES, 'UTF-8');

        if ($template === 'business') {
            $content = str_replace('__SITE_TITLE__', $safeTitle, $this->businessStarterIndex());
        } else {
            $content = <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$safeTitle}</title>
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      font-family: Arial, sans-serif;
      background: #F8FAFC;
      color: #e2dddd;
    }
    main {
      max-width: 720px;
      padding: 40px;
      text-align: center;
    }
    h1 {
      color: #0D6EFD;
    }
  </style>
</head>
<body>
  <main>
    <p>{$templateLabel} starter project</p>
    <h1>{$safeTitle}</h1>
    <p>Your Instaweb website is ready. Edit this file, preview your changes, and publish when you are done.</p>
  </main>
</body>
</html>
HTML;
        }

        $target = $projectPath . DIRECTORY_SEPARATOR . 'index.html';
        if (file_put_contents($target, $content) === false) {
            throw new \RuntimeException('Unable to create starter index.html.');
        }

        return filesize($target) ?: strlen($content);
    }


    /**
     * A complete, responsive business landing page. Content is deliberately
     * static so customers can edit it with the existing file manager/editor.
     */
    private function businessStarterIndex(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="A professional business website. Update this description with your business details.">
  <meta name="theme-color" content="#10251f">
  <title>__SITE_TITLE__</title>
  <style>
    :root{--ink:#17231f;--muted:#66736d;--green:#176b50;--green-dark:#10251f;--mint:#e8f1eb;--paper:#fbfaf7;--line:#e6e9e4;--white:#fff;--gold:#d8a85b}
    *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--paper);color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;line-height:1.6}a{color:inherit}img{max-width:100%}.container{width:min(1160px,calc(100% - 48px));margin:auto}
    .topbar{background:var(--green-dark);color:#dce8e1;font-size:13px}.topbar .container{display:flex;justify-content:space-between;gap:16px;padding:9px 0}.topbar a{text-decoration:none}
    header{background:rgba(251,250,247,.96);border-bottom:1px solid var(--line);position:sticky;top:0;z-index:5;backdrop-filter:blur(12px)}.nav{min-height:78px;display:flex;align-items:center;justify-content:space-between;gap:28px}.brand{font-size:21px;font-weight:850;letter-spacing:-.7px;text-decoration:none}.brand-mark{display:inline-grid;place-items:center;width:34px;height:34px;margin-right:9px;border-radius:11px;background:var(--green);color:white;font-size:15px}.nav-links{display:flex;align-items:center;gap:30px}.nav-links a{text-decoration:none;color:#4e5d55;font-size:14px;font-weight:650}.nav-links a:hover{color:var(--green)}.nav-cta,.button{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:0 21px;border-radius:7px;background:var(--green);color:white;text-decoration:none;font-weight:750;font-size:14px;border:1px solid var(--green);transition:.2s}.nav-cta:hover,.button:hover{background:var(--green-dark);border-color:var(--green-dark);transform:translateY(-1px)}.menu-toggle{display:none;border:1px solid var(--line);background:white;border-radius:7px;padding:10px 13px;font:inherit}
    .hero{padding:94px 0 88px;background:radial-gradient(ellipse at 82% 25%,#e2ecdf 0,transparent 38%),var(--paper)}.hero-grid{display:grid;grid-template-columns:1.08fr .92fr;gap:70px;align-items:center}.eyebrow{display:flex;align-items:center;gap:10px;color:var(--green);font-size:12px;font-weight:850;letter-spacing:1.7px;text-transform:uppercase}.eyebrow:before{content:"";width:28px;height:2px;background:var(--gold)}h1,h2,h3,p{margin-top:0}.hero h1{font-size:clamp(42px,6vw,70px);line-height:1.04;letter-spacing:-3px;max-width:680px;margin:22px 0}.hero h1 span{color:var(--green)}.hero-copy{max-width:540px;color:var(--muted);font-size:18px}.hero-actions{display:flex;gap:13px;flex-wrap:wrap;margin-top:30px}.button.secondary{background:transparent;color:var(--ink);border-color:#cbd4cc}.hero-note{margin-top:27px;color:var(--muted);font-size:13px}.visual{min-height:430px;position:relative;display:grid;place-items:center}.visual-panel{width:min(100%,440px);height:390px;border-radius:16px;background:linear-gradient(145deg,#244f3e,#10251f);position:relative;overflow:hidden;box-shadow:0 28px 65px #10251f25}.visual-panel:before,.visual-panel:after{content:"";position:absolute;border:1px solid #ffffff25;border-radius:50%;width:370px;height:370px;right:-100px;top:-95px}.visual-panel:after{width:250px;height:250px;right:-35px;top:-35px}.visual-copy{position:absolute;inset:auto 32px 34px;color:white;z-index:1}.visual-copy small{color:#d8bd8e;letter-spacing:2px;text-transform:uppercase;font-size:11px;font-weight:800}.visual-copy strong{display:block;font-size:34px;line-height:1.1;max-width:280px;margin-top:12px}.visual-number{position:absolute;top:32px;left:32px;color:#ffffffc7;font-size:12px;letter-spacing:2px}.floating-card{position:absolute;right:-15px;top:62px;background:white;padding:17px 20px;border-radius:10px;box-shadow:0 15px 40px #17231f18;font-size:13px}.floating-card strong{display:block;font-size:22px;color:var(--green)}
    .trust{border-top:1px solid var(--line);border-bottom:1px solid var(--line);background:white}.trust .container{display:grid;grid-template-columns:repeat(3,1fr);padding:25px 0}.trust-item{padding:5px 28px;border-right:1px solid var(--line)}.trust-item:first-child{padding-left:0}.trust-item:last-child{border:0}.trust-item strong{display:block;font-size:15px}.trust-item span{font-size:13px;color:var(--muted)}
    section.content-section{padding:88px 0}.section-head{max-width:650px;margin-bottom:36px}.section-head .eyebrow{margin-bottom:15px}.section-head h2{font-size:clamp(30px,4vw,44px);letter-spacing:-1.6px;line-height:1.12;margin-bottom:13px}.section-head p{color:var(--muted);font-size:16px}.services{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.service{background:white;border:1px solid var(--line);border-radius:12px;padding:28px;min-height:225px}.service-icon{width:44px;height:44px;border-radius:12px;background:var(--mint);display:grid;place-items:center;color:var(--green);font-size:20px;font-weight:800}.service h3{font-size:19px;margin:21px 0 8px}.service p{color:var(--muted);font-size:14px;margin-bottom:0}.about{background:var(--mint)}.about-grid{display:grid;grid-template-columns:.9fr 1.1fr;gap:75px;align-items:center}.about-art{min-height:330px;border-radius:14px;background:linear-gradient(135deg,#d5e3d6,#9fbca7);display:grid;place-items:center;color:#24543e;font-size:68px;font-weight:900;letter-spacing:-5px}.about-copy h2{font-size:clamp(30px,4vw,43px);line-height:1.12;letter-spacing:-1.5px}.about-copy p{color:var(--muted)}.check-list{padding:0;list-style:none;display:grid;gap:11px;margin:24px 0 0}.check-list li:before{content:"✓";color:var(--green);font-weight:900;margin-right:11px}
    .process-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:28px}.step{border-top:2px solid var(--green);padding-top:18px}.step span{color:var(--gold);font-size:12px;font-weight:900;letter-spacing:1px}.step h3{margin:10px 0 7px}.step p{color:var(--muted);font-size:14px}.cta{background:var(--green-dark);color:white;padding:65px 0}.cta-inner{display:flex;align-items:center;justify-content:space-between;gap:28px}.cta h2{font-size:clamp(28px,4vw,40px);line-height:1.15;max-width:650px;margin:0}.cta .button{background:white;color:var(--green-dark);border-color:white;white-space:nowrap}
    footer{background:#0b1b15;color:#dbe5df;padding:48px 0 24px}.footer-grid{display:grid;grid-template-columns:1.4fr 1fr 1fr;gap:40px;padding-bottom:38px}.footer-brand{font-size:20px;font-weight:850}.footer-grid p,.footer-grid a{color:#aabbb0;font-size:13px}.footer-grid a{display:block;text-decoration:none;margin:7px 0}.footer-grid h3{font-size:13px;color:white}.copyright{border-top:1px solid #ffffff20;padding-top:20px;color:#93a69a;font-size:12px}
    @media(max-width:800px){.container{width:min(100% - 32px,600px)}.nav{min-height:68px}.menu-toggle{display:block}.nav-links{display:none;position:absolute;top:68px;left:0;right:0;padding:18px 24px 24px;background:var(--paper);border-bottom:1px solid var(--line);flex-direction:column;align-items:stretch;gap:18px}.nav-links.is-open{display:flex}.nav-cta{display:none}.hero{padding:62px 0}.hero-grid,.about-grid{grid-template-columns:1fr;gap:32px}.hero h1{letter-spacing:-2px}.visual{min-height:340px}.visual-panel{height:320px}.floating-card{right:0}.trust .container{grid-template-columns:1fr;gap:15px}.trust-item,.trust-item:first-child{padding:0;border:0}.services,.process-grid{grid-template-columns:1fr}.content-section{padding:65px 0!important}.about-art{min-height:230px}.cta-inner{align-items:flex-start;flex-direction:column}.footer-grid{grid-template-columns:1fr 1fr}.footer-grid>div:first-child{grid-column:1/-1}}
    @media(max-width:420px){.footer-grid{grid-template-columns:1fr}.footer-grid>div:first-child{grid-column:auto}.hero-actions .button{width:100%}.visual-copy strong{font-size:29px}}
    @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}*,*:before,*:after{transition:none!important}}
  </style>
</head>
<body>
  <div class="topbar"><div class="container"><span>Professional service. Personal attention.</span><a href="#contact">Get in touch ↗</a></div></div>
  <header><nav class="container nav" aria-label="Main navigation">
    <a class="brand" href="#"><span class="brand-mark">B</span>__SITE_TITLE__</a>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-menu">Menu</button>
    <div class="nav-links" id="site-menu"><a href="#services">Services</a><a href="#about">About</a><a href="#process">How we work</a><a href="#contact">Contact</a></div>
    <a class="nav-cta" href="#contact">Start a conversation</a>
  </nav></header>
  <main>
    <section class="hero"><div class="container hero-grid">
      <div><div class="eyebrow">Welcome to __SITE_TITLE__</div><h1>Good work.<br><span>Built around you.</span></h1><p class="hero-copy">We help people and businesses move forward with reliable services, thoughtful solutions and a commitment to doing things properly.</p><div class="hero-actions"><a class="button" href="#contact">Work with us <span aria-hidden="true">&nbsp;↗</span></a><a class="button secondary" href="#services">Explore services</a></div><p class="hero-note">Replace this introduction with a short, specific description of your business.</p></div>
      <div class="visual" aria-label="Decorative business illustration"><div class="visual-panel"><div class="visual-number">__SITE_TITLE__ / EST. 20XX</div><div class="visual-copy"><small>Thoughtful by design</small><strong>Making your next step a better one.</strong></div></div><div class="floating-card"><strong>01 — 03</strong>What we do</div></div>
    </div></section>
    <div class="trust"><div class="container"><div class="trust-item"><strong>Personal approach</strong><span>Solutions shaped around your needs</span></div><div class="trust-item"><strong>Quality focused</strong><span>Care in every detail and delivery</span></div><div class="trust-item"><strong>Here to help</strong><span>Clear communication from start to finish</span></div></div></div>
    <section class="content-section" id="services"><div class="container"><div class="section-head"><div class="eyebrow">What we do</div><h2>Services that make a difference.</h2><p>Show visitors exactly how your business can help them. Rename these cards and replace the descriptions with your real services.</p></div><div class="services">
      <article class="service"><div class="service-icon" aria-hidden="true">01</div><h3>Service One</h3><p>Describe your first core service, who it helps and the result customers can expect.</p></article>
      <article class="service"><div class="service-icon" aria-hidden="true">02</div><h3>Service Two</h3><p>Explain another service in plain language. Focus on customer needs, not jargon.</p></article>
      <article class="service"><div class="service-icon" aria-hidden="true">03</div><h3>Service Three</h3><p>Highlight a specialist service, product or area where your business stands out.</p></article>
    </div></div></section>
    <section class="content-section about" id="about"><div class="container about-grid"><div class="about-art" aria-hidden="true">YOUR<br>STORY.</div><div class="about-copy"><div class="eyebrow">About the business</div><h2>A business built on doing things well.</h2><p>Introduce your business, its experience and the people behind it. Share what matters to you and why customers choose to work with you.</p><ul class="check-list"><li>Explain what makes your approach different</li><li>Share your experience or credentials</li><li>Show customers what they can expect</li></ul></div></div></section>
    <section class="content-section" id="process"><div class="container"><div class="section-head"><div class="eyebrow">How we work</div><h2>Simple from the first conversation.</h2><p>Set expectations so customers know what happens next.</p></div><div class="process-grid"><article class="step"><span>STEP 01</span><h3>We listen</h3><p>Tell us what you need and what a successful result looks like.</p></article><article class="step"><span>STEP 02</span><h3>We plan</h3><p>We agree on the right approach, timing and next steps.</p></article><article class="step"><span>STEP 03</span><h3>We deliver</h3><p>We do the work and keep you informed along the way.</p></article></div></div></section>
    <section class="cta" id="contact"><div class="container cta-inner"><h2>Have a project in mind? Let's talk about what you need.</h2><a class="button" href="mailto:replace-with-your-email@example.com">Contact the business ↗</a></div></section>
  </main>
  <footer><div class="container"><div class="footer-grid"><div><div class="footer-brand">__SITE_TITLE__</div><p>Replace this line with your business tagline and location.</p></div><div><h3>Explore</h3><a href="#services">Services</a><a href="#about">About us</a><a href="#process">How we work</a></div><div><h3>Contact</h3><a href="mailto:replace-with-your-email@example.com">replace-with-your-email@example.com</a><a href="#contact">Add your phone / WhatsApp number</a><a href="#contact">Add your business location</a></div></div><div class="copyright">© <span id="year">2026</span> __SITE_TITLE__. All rights reserved. Update this footer with your business details.</div></div></footer>
  <script>
    const menuButton=document.querySelector('.menu-toggle');const menu=document.querySelector('#site-menu');
    menuButton.addEventListener('click',()=>{const open=menu.classList.toggle('is-open');menuButton.setAttribute('aria-expanded',String(open));});
    menu.querySelectorAll('a').forEach(link=>link.addEventListener('click',()=>{menu.classList.remove('is-open');menuButton.setAttribute('aria-expanded','false');}));
    document.querySelector('#year').textContent=String(new Date().getFullYear());
  </script>
</body>
</html>
HTML;
    }

    /**
     * Install the preserved WordPress package into a project directory.
     */
    public function installWordPress(string $projectPath): int
    {
        $configuredPackage = (string) env('WORDPRESS_PACKAGE_PATH', 'storage/wordpress/wordpress-6.9.3.zip');
        $packagePath = str_starts_with($configuredPackage, DIRECTORY_SEPARATOR)
            ? $configuredPackage
            : ROOT_PATH . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $configuredPackage);
        if (!is_file($packagePath)) {
            throw new \RuntimeException('The preserved WordPress package is missing.');
        }

        $expectedHash = strtolower(trim((string) env('WORDPRESS_PACKAGE_SHA256', '')));
        if ($expectedHash === '' || !preg_match('/^[a-f0-9]{64}$/', $expectedHash)) {
            throw new \RuntimeException('The WordPress package checksum is not configured.');
        }
        $actualHash = hash_file('sha256', $packagePath);
        if (!is_string($actualHash) || !hash_equals($expectedHash, strtolower($actualHash))) {
            throw new \RuntimeException('The WordPress package checksum failed.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($packagePath) !== true) {
            throw new \RuntimeException('Unable to open the WordPress package.');
        }

        $temporaryPath = $projectPath . '.install-' . bin2hex(random_bytes(8));
        if (!mkdir($temporaryPath, 0755, true) && !is_dir($temporaryPath)) {
            throw new \RuntimeException('Unable to prepare the temporary WordPress folder.');
        }

        // Project backups keep WordPress files at the archive root, while the
        // preserved installer package keeps them in a `wordpress/` directory.
        // Support both trusted layouts so a preserved project backup can be
        // restored as the package when necessary.
        $archivePrefix = $zip->locateName('wordpress/') !== false ? 'wordpress/' : '';
        $totalBytes = 0;
        $fileCount = 0;
        $maxBytes = max(1, (int) env('WORDPRESS_MAX_EXTRACTED_BYTES', 512 * BYTES_PER_MB));
        $maxFiles = max(1, (int) env('WORDPRESS_MAX_FILES', 20000));
        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = str_replace('\\', '/', (string) $zip->getNameIndex($index));
                if (!str_starts_with($name, $archivePrefix)) {
                    continue;
                }

                $relative = substr($name, strlen($archivePrefix));
                if ($relative === '') {
                    continue;
                }
                if (str_contains($relative, "\0") || preg_match('#(^|/)\.\.?(/|$)#', $relative)) {
                    throw new \RuntimeException('The WordPress package contains an unsafe path.');
                }

                if ($fileCount >= $maxFiles) {
                    throw new \RuntimeException('The WordPress package contains too many files.');
                }
                $entry = $zip->statIndex($index);
                $entryBytes = (int) ($entry['size'] ?? 0);
                if ($entryBytes < 0 || $totalBytes > $maxBytes - $entryBytes) {
                    throw new \RuntimeException('The WordPress package is too large to extract.');
                }
                $externalAttributes = 0;
                $attributeType = 0;
                if ($zip->getExternalAttributesIndex($index, $attributeType, $externalAttributes)
                    && $attributeType === \ZipArchive::OPSYS_UNIX
                    && (($externalAttributes >> 16) & 0120000) === 0120000) {
                    throw new \RuntimeException('The WordPress package contains a symbolic link.');
                }

                $target = $temporaryPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
                if (str_ends_with($relative, '/')) {
                    if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
                        throw new \RuntimeException('Unable to create the WordPress folder.');
                    }
                    continue;
                }

                $parent = dirname($target);
                if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
                    throw new \RuntimeException('Unable to create the WordPress folder.');
                }

                $stream = $zip->getStream($name);
                $file = $stream ? fopen($target, 'wb') : false;
                if (!$stream || !$file) {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                    throw new \RuntimeException('Unable to extract the WordPress package.');
                }
                $totalBytes += stream_copy_to_stream($stream, $file);
                $fileCount++;
                fclose($file);
                fclose($stream);
            }
        } catch (\Throwable $exception) {
            $this->deleteProjectTree($temporaryPath);
            throw $exception;
        } finally {
            $zip->close();
        }

        foreach (['index.php', 'wp-admin', 'wp-includes', 'wp-content', 'wp-settings.php', 'wp-login.php'] as $required) {
            if (!file_exists($temporaryPath . DIRECTORY_SEPARATOR . $required)) {
                $this->deleteProjectTree($temporaryPath);
                throw new \RuntimeException('The WordPress package is incomplete.');
            }
        }

        $prefix = 'wp_' . bin2hex(random_bytes(8)) . '_';
        $config = "<?php\n";
        $config .= "define('DB_NAME', " . var_export((string) env('DB_NAME', 'turbohostmw'), true) . ");\n";
        $config .= "define('DB_USER', " . var_export((string) env('DB_USER', ''), true) . ");\n";
        $config .= "define('DB_PASSWORD', " . var_export((string) env('DB_PASS', ''), true) . ");\n";
        $config .= "define('DB_HOST', " . var_export((string) env('DB_HOST', 'localhost'), true) . ");\n";
        $config .= "define('DB_CHARSET', 'utf8mb4');\ndefine('DB_COLLATE', '');\n";
        $config .= "\$table_prefix = " . var_export($prefix, true) . ";\n";
        $config .= "define('WP_DEBUG', false);\n\n";
        $config .= "if (!defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/'); }\nrequire_once ABSPATH . 'wp-settings.php';\n";

        if (file_put_contents($temporaryPath . DIRECTORY_SEPARATOR . 'wp-config.php', $config) === false
            || file_put_contents($temporaryPath . DIRECTORY_SEPARATOR . '.turbohost-wordpress', $prefix) === false) {
            $this->deleteProjectTree($temporaryPath);
            throw new \RuntimeException('Unable to create the WordPress configuration.');
        }

        if (is_dir($projectPath)) {
            $this->deleteProjectTree($projectPath);
        }
        if (!rename($temporaryPath, $projectPath)) {
            $this->deleteProjectTree($temporaryPath);
            throw new \RuntimeException('Unable to finalize the WordPress project.');
        }

        return $totalBytes + strlen($config) + strlen($prefix);
    }

    private function deleteProjectTree(string $path): bool
    {
        if (!is_dir($path)) {
            return true;
        }

        $items = array_diff(scandir($path) ?: [], ['.', '..']);
        foreach ($items as $item) {
            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath) && !is_link($itemPath)) {
                if (!$this->deleteProjectTree($itemPath)) {
                    return false;
                }
            } elseif (!unlink($itemPath)) {
                return false;
            }
        }

        return rmdir($path);
    }

    /**
     * Normalize a browser-submitted project-relative path.
     */
    public function normalizeRelativePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = trim($path, '/');
        $segments = array_values(array_filter(explode('/', $path), static fn (string $segment): bool => $segment !== ''));
        $clean = [];

        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..' || str_contains($segment, "\0")) {
                throw new \InvalidArgumentException('Invalid file path.');
            }

            $safe = preg_replace('/[^A-Za-z0-9_\-\. ]/', '_', $segment);
            $safe = trim((string) $safe);

            if ($safe === '' || str_starts_with($safe, '.')) {
                throw new \InvalidArgumentException('Invalid file path.');
            }

            $clean[] = $safe;
        }

        return implode('/', $clean);
    }

    /**
     * Return a recursive file/folder listing for display.
     */
    public function listProjectFiles(int $userId, string $slug): array
    {
        $projectPath = $this->ensureProjectDirectory($userId, $slug);
        $items = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($projectPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($projectPath) + 1));
            $items[] = [
                'name' => $item->getFilename(),
                'path' => $relative,
                'type' => $item->isDir() ? 'folder' : 'file',
                'size' => $item->isFile() ? $item->getSize() : 0,
                'modified_at' => date('Y-m-d H:i:s', $item->getMTime()),
                'extension' => $item->isFile() ? strtolower(pathinfo($item->getFilename(), PATHINFO_EXTENSION)) : '',
            ];
        }

        usort($items, static function (array $a, array $b): int {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'folder' ? -1 : 1;
            }

            return strcasecmp($a['path'], $b['path']);
        });

        return $items;
    }

    /**
     * Calculate a project folder's total file size.
     */
    public function directorySize(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $bytes = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $bytes += $item->getSize();
            }
        }

        return $bytes;
    }

    /**
     * Write a starter text file inside a project.
     */
    public function createTextFile(int $userId, string $slug, string $relativePath): int
    {
        $safeRelativePath = $this->normalizeRelativePath($relativePath);
        $targetPath = $this->resolveProjectPath($userId, $slug, $safeRelativePath);
        $parent = dirname($targetPath);

        if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
            throw new \RuntimeException('Unable to create folder.');
        }

        if (is_file($targetPath) || is_dir($targetPath)) {
            throw new \RuntimeException('A file or folder with that name already exists.');
        }

        $extension = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
        $content = $extension === 'html'
            ? "<!doctype html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"utf-8\">\n  <title>New page</title>\n</head>\n<body>\n  <h1>New page</h1>\n</body>\n</html>\n"
            : '';

        if (file_put_contents($targetPath, $content) === false) {
            throw new \RuntimeException('Unable to create file.');
        }

        return filesize($targetPath) ?: strlen($content);
    }

    /**
     * Create a folder inside a project.
     */
    public function createFolder(int $userId, string $slug, string $relativePath): void
    {
        $targetPath = $this->resolveProjectPath($userId, $slug, $relativePath);

        if (is_file($targetPath) || is_dir($targetPath)) {
            throw new \RuntimeException('A file or folder with that name already exists.');
        }

        if (!mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
            throw new \RuntimeException('Unable to create folder.');
        }
    }

    /**
     * Copy a file or directory within the same draft project.
     */
    public function copyProjectItem(int $userId, string $slug, string $fromRelativePath, string $toRelativePath): array
    {
        $from = $this->normalizeRelativePath($fromRelativePath);
        $to = $this->normalizeRelativePath($toRelativePath);
        if ($from === '' || $to === '' || $from === $to || str_starts_with($to, $from . '/')) {
            throw new \InvalidArgumentException('Choose a valid destination outside the source folder.');
        }

        $projectPath = $this->projectPath($userId, $slug);
        $sourcePath = $this->resolveProjectPath($userId, $slug, $from);
        $targetPath = $this->resolveProjectPath($userId, $slug, $to);
        if (!$this->isInsidePath($sourcePath, $projectPath) || (!is_file($sourcePath) && !is_dir($sourcePath))) {
            throw new \RuntimeException('Source file or folder was not found.');
        }
        if (file_exists($targetPath)) {
            throw new \RuntimeException('A file or folder already exists at the destination.');
        }

        $copyTree = function (string $source, string $target) use (&$copyTree): void {
            if (is_link($source)) {
                throw new \RuntimeException('Symbolic links cannot be copied.');
            }
            if (is_file($source)) {
                $parent = dirname($target);
                if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
                    throw new \RuntimeException('Unable to create destination folder.');
                }
                if (!copy($source, $target)) {
                    throw new \RuntimeException('Unable to copy file.');
                }
                return;
            }
            if (!mkdir($target, 0755, true) && !is_dir($target)) {
                throw new \RuntimeException('Unable to create destination folder.');
            }
            foreach (array_diff(scandir($source) ?: [], ['.', '..']) as $entry) {
                $copyTree($source . DIRECTORY_SEPARATOR . $entry, $target . DIRECTORY_SEPARATOR . $entry);
            }
        };

        try {
            $copyTree($sourcePath, $targetPath);
        } catch (\Throwable $exception) {
            if (is_dir($targetPath)) {
                $this->deleteDirectory($targetPath);
            } elseif (is_file($targetPath)) {
                unlink($targetPath);
            }
            throw $exception;
        }

        return [
            'from' => $from,
            'to' => $to,
            'type' => is_dir($targetPath) ? 'folder' : 'file',
            'size' => is_file($targetPath) ? (filesize($targetPath) ?: 0) : $this->directorySize($targetPath),
        ];
    }

    /**
     * Delete a safe project-relative file or folder.
     */
    public function deleteProjectItem(int $userId, string $slug, string $relativePath): void
    {
        $safeRelativePath = $this->normalizeRelativePath($relativePath);

        if ($safeRelativePath === '') {
            throw new \InvalidArgumentException('Select a file or folder to delete.');
        }

        $targetPath = $this->resolveProjectPath($userId, $slug, $safeRelativePath);
        $projectPath = $this->projectPath($userId, $slug);

        if (!$this->isInsidePath($targetPath, $projectPath)) {
            throw new \InvalidArgumentException('Invalid file path.');
        }

        if (is_dir($targetPath)) {
            $this->deleteDirectory($targetPath);
            return;
        }

        if (is_file($targetPath)) {
            unlink($targetPath);
        }
    }

    /**
     * Move or rename a draft file/folder inside the same project.
     */
    public function moveProjectItem(int $userId, string $slug, string $fromRelativePath, string $toRelativePath): array
    {
        $from = $this->normalizeRelativePath($fromRelativePath);
        $to = $this->normalizeRelativePath($toRelativePath);

        if ($from === '' || $to === '') {
            throw new \InvalidArgumentException('Choose both source and destination paths.');
        }

        $projectPath = $this->projectPath($userId, $slug);
        $sourcePath = $this->resolveProjectPath($userId, $slug, $from);
        $targetPath = $this->resolveProjectPath($userId, $slug, $to);

        if (!$this->isInsidePath($sourcePath, $projectPath)) {
            throw new \InvalidArgumentException('Invalid source path.');
        }

        if (is_file($targetPath) || is_dir($targetPath)) {
            throw new \RuntimeException('A file or folder already exists at the destination.');
        }

        if (!is_file($sourcePath) && !is_dir($sourcePath)) {
            throw new \RuntimeException('Source file or folder was not found.');
        }

        $parent = dirname($targetPath);
        if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
            throw new \RuntimeException('Unable to create destination folder.');
        }

        if (!rename($sourcePath, $targetPath)) {
            throw new \RuntimeException('Unable to move the item.');
        }

        return [
            'from' => $from,
            'to' => $to,
            'type' => is_dir($targetPath) ? 'folder' : 'file',
            'size' => is_file($targetPath) ? (filesize($targetPath) ?: 0) : 0,
        ];
    }

    /**
     * Resolve a downloadable file path and return metadata.
     */
    public function downloadableFile(int $userId, string $slug, string $relativePath): array
    {
        $safeRelativePath = $this->normalizeRelativePath($relativePath);
        $projectPath = $this->projectPath($userId, $slug);
        $targetPath = $this->resolveProjectPath($userId, $slug, $safeRelativePath);

        if (!$this->isInsidePath($targetPath, $projectPath) || !is_file($targetPath)) {
            throw new \RuntimeException('File was not found.');
        }

        return [
            'absolute_path' => $targetPath,
            'relative_path' => $safeRelativePath,
            'filename' => basename($targetPath),
            'size' => filesize($targetPath) ?: 0,
            'mime' => $this->detectMimeType($targetPath),
        ];
    }

    /**
     * Read a project text file after safe path checks.
     */
    public function readTextFile(int $userId, string $slug, string $relativePath): array
    {
        $file = $this->downloadableFile($userId, $slug, $relativePath);
        $extension = strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION));

        if (!in_array($extension, ['html', 'htm', 'css', 'js', 'json', 'txt', 'md'], true)) {
            throw new \InvalidArgumentException('This file type cannot be edited.');
        }

        $content = file_get_contents($file['absolute_path']);
        if ($content === false) {
            throw new \RuntimeException('Unable to read file.');
        }

        $file['content'] = $content;
        $file['extension'] = $extension;

        return $file;
    }

    /**
     * Save a project text file after safe path checks.
     */
    public function writeTextFile(int $userId, string $slug, string $relativePath, string $content): int
    {
        $file = $this->readTextFile($userId, $slug, $relativePath);

        if (file_put_contents($file['absolute_path'], $content) === false) {
            throw new \RuntimeException('Unable to save file.');
        }

        return filesize($file['absolute_path']) ?: strlen($content);
    }

    /**
     * Delete a project folder only when it is inside the projects storage root.
     */
    public function deleteProjectDirectory(int $userId, string $slug): void
    {
        $path = $this->projectPath($userId, $slug);

        if (!is_dir($path) || !$this->isInsideProjectsRoot($path)) {
            return;
        }

        $this->deleteDirectory($path);
    }

    /**
     * Normalize a template key into a display label.
     */
    private function templateLabel(string $template): string
    {
        $labels = [
            'blank' => 'Blank',
            'portfolio' => 'Portfolio',
            'business' => 'Business',
            'church' => 'Church',
            'blog' => 'Blog',
            'school' => 'School project',
        ];

        return $labels[$template] ?? 'Blank';
    }

    /**
     * Confirm a directory resolves under the projects root.
     */
    private function isInsideProjectsRoot(string $path): bool
    {
        $root = realpath($this->projectsRoot());
        $target = realpath($path);

        if (!$root || !$target) {
            return false;
        }

        return str_starts_with(strtolower($target), strtolower($root . DIRECTORY_SEPARATOR));
    }

    /**
     * Confirm a target path stays inside the given base path.
     */
    private function isInsidePath(string $targetPath, string $basePath): bool
    {
        $base = realpath($basePath);
        $target = realpath($targetPath);

        if (!$base || !$target) {
            return false;
        }

        return $target === $base || str_starts_with(strtolower($target), strtolower($base . DIRECTORY_SEPARATOR));
    }

    /**
     * Detect a safe response MIME type for downloads.
     */
    private function detectMimeType(string $path): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);

                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }

        return 'application/octet-stream';
    }

    /**
     * Recursively remove a directory tree.
     */
    private function deleteDirectory(string $path): void
    {
        $items = array_diff(scandir($path) ?: [], ['.', '..']);

        foreach ($items as $item) {
            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                $this->deleteDirectory($itemPath);
                continue;
            }

            unlink($itemPath);
        }

        rmdir($path);
    }
}
