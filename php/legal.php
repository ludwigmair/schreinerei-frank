<?php

/**
 * Firmenname xxx – Rechtsseiten (Impressum / Datenschutz).
 * Erwartet: $s (Daten), $page ('impressum'|'datenschutz'), $baseHref.
 */

declare(strict_types=1);

$legal = $s['legal'][$page] ?? [];
$business = $s['business'] ?? [];
$baseHref = $baseHref ?? './';

function legal_block_dl(array $legal, string $key, string $label): void
{
  $block = $legal[$key] ?? null;
  if (!$block) return;
  echo '<section class="legal__block">' . "\n";
  echo '  <h2>' . site_esc($block['kicker'] ?? $label) . "</h2>\n";
  $text = $block['text'] ?? '';
  $cls = ($key === 'controller') ? ' legal__pre' : '';
  echo '  <p' . $cls . '>' . nl2br(site_esc($text)) . "</p>\n";
  echo "</section>\n";
}
?>

<div class="legal wrap">
  <header class="legal__head">
    <p class="kicker"><a href="<?php echo $baseHref; ?>index.php"><?php echo site_esc(site_get_str($ui, 'legalBack', '← Zurück')); ?></a></p>
    <h1><?php echo site_esc(site_get_str($legal, 'heading', '')); ?></h1>
    <?php if ($page === 'impressum'): ?>
      <p><?php echo site_esc(site_get_str($legal, 'intro', '')); ?></p>
    <?php else: ?>
      <p class="legal__intro"><?php echo site_esc(site_get_str($legal, 'intro', '')); ?></p>
    <?php endif; ?>
  </header>

  <?php if ($page === 'impressum'): ?>
    <section class="legal__block">
      <h2><?php echo site_esc(site_get_str($legal, 'companyName', '')); ?></h2>
      <p><?php echo site_esc(site_get_str($legal, 'ownerName', '')); ?><br>
        <span><?php echo site_esc(site_get_str($legal, 'address', '')); ?></span>
      </p>
      <p><?php echo site_esc(site_get_str($ui, 'legalPhoneLabel', 'Telefon:')); ?> <a href="<?php echo site_esc(site_get_str($business, 'phoneHref', 'tel:')); ?>"><?php echo site_esc(site_get_str($business, 'phone', '')); ?></a><br>
        <?php echo site_esc(site_get_str($ui, 'legalFaxLabel', 'Telefax:')); ?> <span><?php echo site_esc(site_get_str($business, 'fax', '')); ?></span><br>
        <?php echo site_esc(site_get_str($ui, 'legalEmailLabel', 'E-Mail:')); ?> <a href="mailto:<?php echo site_esc(site_get_str($legal, 'contact.emailHref', '')); ?>"><?php echo site_esc(site_get_str($legal, 'contact.emailHref', '')); ?></a><br>
        <?php echo site_esc(site_get_str($ui, 'legalInternetLabel', 'Internet:')); ?> <a href="<?php echo site_esc(site_get_str($legal, 'contact.url', '')); ?>"><?php echo site_esc(site_get_str($legal, 'contact.url', '')); ?></a></p>
    </section>

    <?php
    legal_block_dl($legal, 'vat', 'Umsatzsteuer-ID');
    legal_block_dl($legal, 'responsible', 'Redaktionell verantwortlich');
    legal_block_dl($legal, 'dispute', 'EU-Streitschlichtung');
    legal_block_dl($legal, 'contentLiability', 'Haftung für Inhalte');
    legal_block_dl($legal, 'linkLiability', 'Haftung für Links');
    legal_block_dl($legal, 'copyright', 'Urheberrecht');
    ?>
  <?php else: ?>
    <?php
    legal_block_dl($legal, 'controller', '1. Verantwortlicher');
    legal_block_dl($legal, 'processing', '2. Allgemeines zur Datenverarbeitung');
    legal_block_dl($legal, 'serverLogs', '3. Server-Logfiles');
    legal_block_dl($legal, 'cookies', '4. Cookies');
    legal_block_dl($legal, 'contactForm', '5. Kontaktformular');
    legal_block_dl($legal, 'googleMaps', '6. Google Maps');
    legal_block_dl($legal, 'hosting', '7. Hosting');
    legal_block_dl($legal, 'rights', '8. Ihre Rechte als betroffene Person');
    legal_block_dl($legal, 'supervisoryAuthority', '9. Aufsichtsbehörde');
    ?>
  <?php endif; ?>
</div>