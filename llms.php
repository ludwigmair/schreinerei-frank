<?php
/**
 * llms.txt (dynamisch aus site.json) – wird über .htaccess als llms.txt
 * ausgeliefert. Für KI-/LLM-Tools: kompakter, strukturierter Überblick.
 *
 * Alle Beschriftungen (Headings/Prefixe) kommen aus ui.llms, alle Inhalte
 * aus den redaktionellen Blöcken von site.json – keine harten Texte hier.
 */
declare(strict_types=1);

require_once __DIR__ . '/php/data.php';

$s = site_load();
$b = $s['business'] ?? [];
$meta = $s['meta'] ?? [];
$ui = $s['ui']['llms'] ?? [];
$base = site_base($s);

header('Content-Type: text/plain; charset=utf-8');

$out = '';
$out .= '# ' . site_get_str($b, 'name', 'Schreinerei Frank') . "\n\n";
$out .= '> ' . site_get_str($meta, 'description', '') . "\n\n";
$out .= '## ' . site_get_str($ui, 'headingServices', 'Leistungen') . "\n";
foreach (($s['services'] ?? []) as $sv) {
    $out .= '- ' . site_get_str($sv, 'title', '') . ': ' . site_get_str($sv, 'text', '') . "\n";
}
$out .= "\n## " . site_get_str($ui, 'headingApproach', 'Arbeitsweise') . "\n";
$about = $s['about'] ?? [];
$out .= '- ' . site_get_str($about, 'text', '') . "\n";
foreach (($about['points'] ?? []) as $pt) {
    $out .= '- ' . $pt . "\n";
}
$out .= "\n## " . site_get_str($ui, 'headingServiceArea', 'Einzugsgebiet') . "\n";
if (!empty($b['areaServed']) && is_array($b['areaServed'])) {
    $out .= '- ' . implode(', ', $b['areaServed']) . ' ' . site_get_str($b, 'areaServedNote', '') . "\n";
}
$out .= "\n## " . site_get_str($ui, 'headingContact', 'Kontakt') . "\n";
$out .= '- ' . site_get_str($ui, 'labelAddress', 'Adresse:') . ' ' . site_get_str($b, 'street', '') . ', ' . site_get_str($b, 'postalCode', '') . ' ' . site_get_str($b, 'city', '') . ', ' . site_get_str($b, 'country', '') . "\n";
$out .= '- ' . site_get_str($ui, 'labelPhone', 'Telefon:') . ' ' . site_get_str($b, 'phone', '') . "\n";
$out .= '- ' . site_get_str($ui, 'labelEmail', 'E-Mail:') . ' ' . site_get_str($b, 'email', '') . "\n";
$out .= '- ' . site_get_str($ui, 'labelHours', 'Öffnungszeiten:') . ' ' . site_get_str($b, 'openingHoursText', '') . "\n";
$out .= '- ' . site_get_str($ui, 'labelWebsite', 'Website:') . ' ' . $base . '/';

echo $out;
