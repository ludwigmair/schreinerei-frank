<?php

/**
 * Firmenname xxx – Footer-Partial (für alle Seiten).
 * Erwartet: $s (Daten), $baseHref.
 */

declare(strict_types=1);

$b = $s['business'] ?? [];
$footer = $s['footer'] ?? [];
$ui = $s['ui'] ?? [];
$baseHref = site_set_base($baseHref ?? './');
?>
</main>

<footer class="site-footer">
  <div class="wrap site-footer__inner">
    <div>
      <b><?php echo site_esc(site_get_str($b, 'name', '')); ?></b><br>
      <span><?php echo site_esc(site_get_str($b, 'street', '')); ?></span> · <span><?php echo site_esc(site_get_str($b, 'postalCode', '')); ?></span> <span><?php echo site_esc(site_get_str($b, 'city', '')); ?></span>
    </div>
    <div>
      Tel. <a href="<?php echo site_esc(site_get_str($b, 'phoneHref', 'tel:')); ?>"><?php echo site_esc(site_get_str($b, 'phone', '')); ?></a><br>
      <a href="mailto:<?php echo site_esc(site_get_str($b, 'email', '')); ?>"><?php echo site_esc(site_get_str($b, 'email', '')); ?></a>
    </div>
    <div>
      <?php echo site_esc(site_get_str($ui, 'footerInhaberLabel', 'Inhaber')); ?> <span><?php echo site_esc(site_get_str($b, 'owner', '')); ?></span><br>
      <a href="<?php echo $baseHref; ?>impressum/"><?php echo site_esc(site_get_str($ui, 'footerImpressum', 'Impressum')); ?></a> · <a href="<?php echo $baseHref; ?>datenschutz/"><?php echo site_esc(site_get_str($ui, 'footerDatenschutz', 'Datenschutz')); ?></a>
    </div>
  </div>
</footer>

<nav class="sticky-bar" data-sticky aria-label="<?php echo site_esc(site_get_str($ui, 'stickyAria', 'Schnellzugriff')); ?>">
  <a class="sticky-bar__item" href="<?php echo site_esc(site_get_str($b, 'phoneHref', 'tel:')); ?>" aria-label="<?php echo site_esc(site_get_str($ui, 'stickyCall', 'Anrufen')); ?>">
    <svg class="sticky-bar__icon" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.6A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 12a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2Z" />
    </svg>
    <span class="sticky-bar__label"><?php echo site_esc(site_get_str($ui, 'stickyCall', 'Anrufen')); ?></span>
  </a>
  <a class="sticky-bar__item" href="mailto:<?php echo site_esc(site_get_str($b, 'email', '')); ?>" data-sticky-mail aria-label="<?php echo site_esc(site_get_str($ui, 'stickyMailAria', 'E-Mail schreiben')); ?>">
    <svg class="sticky-bar__icon" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M4 6h16v12H4zM4 7l8 6 8-6" />
    </svg>
    <span class="sticky-bar__label"><?php echo site_esc(site_get_str($ui, 'stickyMail', 'Mail')); ?></span>
  </a>
  <a class="sticky-bar__item" href="<?php echo $baseHref; ?>index.php#kontakt" aria-label="<?php echo site_esc(site_get_str($ui, 'stickyContactAria', 'Anfrage / Kontaktformular')); ?>">
    <svg class="sticky-bar__icon" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z" />
      <path d="M7.5 9h9M7.5 12.5h6" />
    </svg>
    <span class="sticky-bar__label"><?php echo site_esc(site_get_str($ui, 'stickyContact', 'Anfrage')); ?></span>
  </a>
  <button class="sticky-bar__item sticky-bar__item--btn" type="button" data-to-top aria-label="<?php echo site_esc(site_get_str($ui, 'stickyTopAria', 'Nach oben scrollen')); ?>">
    <svg class="sticky-bar__icon" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M12 19V5M5 12l7-7 7 7" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
    <span class="sticky-bar__label"><?php echo site_esc(site_get_str($ui, 'stickyTop', 'Nach oben')); ?></span>
  </button>
</nav>

<script src="<?php echo site_abs('/js/main.js'); ?>?ver=2.4.0"></script>
</body>

</html>