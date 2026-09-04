<?php

/**
 * Firmenname xxx – Header-Partial (für alle Seiten).
 * Erwartet: $s (Daten), $active (optional markiertes Nav-Label).
 */

declare(strict_types=1);

$b = $s['business'] ?? [];
$nav = $s['nav'] ?? [];
$ui = $s['ui'] ?? [];
$baseHref = site_set_base($baseHref ?? './');
?>
<!doctype html>
<html lang="de" class="no-js">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script>
    document.documentElement.className = document.documentElement.className.replace('no-js', 'js');
  </script>
  <?php echo seo_head($s, $page ?? ''); ?>
</head>

<body data-page="<?php echo site_esc($page ?? ''); ?>">

  <a class="skip-link" href="#inhalt"><?php echo site_esc(site_get_str($ui, 'skipLink', 'Zum Inhalt springen')); ?></a>

  <div class="cookie-banner" data-cookie-banner role="dialog" aria-modal="false" aria-label="<?php echo site_esc(site_get_str($ui, 'cookieAria', 'Cookie-Hinweis')); ?>" hidden>
    <div class="cookie-banner__inner">
      <p class="cookie-banner__text"><?php echo site_esc(site_get_str($ui, 'cookieText', 'Wir verwenden Cookies, um unsere Website nutzerfreundlicher zu gestalten.')); ?></p>
      <div class="cookie-banner__actions">
        <button class="btn btn--block" type="button" data-cookie-accept><?php echo site_esc(site_get_str($ui, 'cookieAccept', 'Alle akzeptieren')); ?></button>
        <button class="btn btn--ghost btn--block" type="button" data-cookie-decline><?php echo site_esc(site_get_str($ui, 'cookieDecline', 'Nur notwendige')); ?></button>
      </div>
      <a class="cookie-banner__link" href="<?php echo $baseHref; ?>datenschutz/"><?php echo site_esc(site_get_str($ui, 'cookieLink', 'Datenschutzerklärung')); ?></a>
    </div>
  </div>

  <header class="site-header">
    <div class="wrap site-header__inner">
      <a class="brand" href="<?php echo $baseHref; ?>index.php" aria-label="<?php echo site_esc(site_get_str($ui, 'brandAria', 'Schreinerei Frank – Startseite')); ?>">
        <img class="brand__logo" src="<?php echo site_asset($s, 'site/frank-logo-opt.png'); ?>" width="54" height="70" alt="" loading="eager">
        <span class="brand__name"><?php echo site_esc(site_get_str($b, 'name', 'Schreinerei Frank')); ?></span>
      </a>

      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" aria-label="<?php echo site_esc(site_get_str($ui, 'navToggleAria', 'Menü öffnen')); ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M3 6h18M3 12h18M3 18h18" stroke-linecap="round" />
        </svg>
      </button>

      <nav class="primary-nav" id="primary-nav" aria-label="<?php echo site_esc(site_get_str($ui, 'navAria', 'Hauptnavigation')); ?>">
        <ul>
          <?php foreach ($nav as $item): ?>
            <li><a href="<?php echo $baseHref; ?>index.php<?php echo site_esc($item['href'] ?? '#'); ?>"><?php echo site_esc($item['label'] ?? ''); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>

      <a class="tel-btn" href="<?php echo site_esc(site_get_str($b, 'phoneHref', 'tel:')); ?>" aria-label="<?php echo site_esc(site_get_str($ui, 'telBtnAria', 'Anrufen')); ?>">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 12a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2Z" />
        </svg>
        <span class="tel-btn__num"><?php echo site_esc(site_get_str($b, 'phone', '')); ?></span>
      </a>
    </div>
  </header>

  <p id="render-error" hidden style="background:#a03020;color:#fff;padding:12px;text-align:center">
    Inhalte konnten nicht geladen werden. Bitte Seite neu laden.
  </p>

  <main id="inhalt">