<?php

/**
 * Web-App-Manifest (dynamisch aus site.json) – wird über .htaccess als
 * /site.webmanifest ausgeliefert, damit es bei jeder Änderung automatisch
 * aktuell bleibt (auch ohne vorherigen Seitenaufruf).
 *
 * Wert-Quellen (Single Source of Truth):
 *  - name/short_name    → config.name   (über business.name-Spiegel)
 *  - theme_color        → config.themeColor
 *  - Icon-/Asset-Pfade  → config.assetsBase
 *  - description        → meta.description (redaktionell, Admin-gepflegt)
 */

declare(strict_types=1);

require_once __DIR__ . '/php/data.php';

$s = site_load();

header('Content-Type: application/manifest+json; charset=utf-8');

$name = $s['business']['name'] ?? 'Schreinerei Frank';
$assets = site_config($s, 'project.assetsBase', '/assets');
$description = $s['meta']['description'] ?? 'Meisterschreinerei in Seeon im Chiemgau – Küchen, Treppen, Türen und Möbel aus Massivholz.';

$manifest = [
    'name'             => $name,
    'short_name'       => $name,
    'description'      => $description,
    'lang'             => 'de',
    'start_url'        => '/',
    'scope'            => '/',
    'display'          => 'browser',
    'theme_color'      => site_config($s, 'project.themeColor', '#3D6490'),
    'background_color' => '#FFFFFF',
    'icons'            => [
        ['src' => '/favicon.ico', 'type' => 'image/x-icon', 'sizes' => 'any'],
        ['src' => $assets . '/site/icon-192.png', 'type' => 'image/png', 'sizes' => '192x192'],
        ['src' => $assets . '/site/icon-512.png', 'type' => 'image/png', 'sizes' => '512x512'],
    ],
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
