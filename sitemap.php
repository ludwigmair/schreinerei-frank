<?php
/**
 * Sitemap (dynamisch aus site.json) – wird über .htaccess als sitemap.xml
 * ausgeliefert, damit sie bei Content-Änderungen automatisch aktuell bleibt.
 */
declare(strict_types=1);

require_once __DIR__ . '/php/data.php';

$s = site_load();
$base = site_base($s);

header('Content-Type: application/xml; charset=utf-8');

$pages = [
    ['loc' => $base . '/', 'priority' => '1.0', 'changefreq' => 'monthly'],
    ['loc' => $base . '/impressum/', 'priority' => '0.3', 'changefreq' => 'yearly'],
    ['loc' => $base . '/datenschutz/', 'priority' => '0.3', 'changefreq' => 'yearly'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($pages as $p) {
    echo '  <url>' . "\n";
    echo '    <loc>' . site_esc($p['loc']) . "</loc>\n";
    echo '    <lastmod>' . date('Y-m-d') . "</lastmod>\n";
    echo '    <changefreq>' . $p['changefreq'] . "</changefreq>\n";
    echo '    <priority>' . $p['priority'] . "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>' . "\n";
