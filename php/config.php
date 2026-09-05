<?php

/**
 * Firmenname xxx– Lese-/Bridge-Datei für die zentrale Projekt-Konfiguration.
 *
 * ALLE projektbezogenen Werte (Firmenname, Domain, E-Mail, Telefon, Theme-Farbe,
 * Asset-Basis, Admin-Pfad) liegen in data/site.json im Top-level-"config"-Block.
 * Diese Datei liest sie aus und stellt sie $s['config'] bereit – außerdem
 * generiert sie daraus alle "Build-Dateien", die PHP zur Laufzeit nicht von
 * selbst servieren kann: die Apache-.htaccess und package.json (das
 * Web-Manifest site.webmanifest wird dynamisch über webmanifest.php
 * ausgeliefert). Die Quelle ist in jedem Fall der config-Block aus site.json.
 *
 * Zwei Verwendungen:
 *
 * 1) Als PHP-Include (für die Webseite). data.php bindet diese Datei ein und
 *    erhält das Projekt-Konfig-Array als Rückgabewert des require:
 *
 *        $config = require __DIR__ . '/config.php';
 *
 * 2) Als CLI-Skript zum (Re-)Generieren der Build-Dateien:
 *
 *        php php/config.php --gen-htaccess    # nur .htaccess
 *        php php/config.php --gen             # alle Build-Dateien
 *
 * Beim normalen Seitenzugriff hält Bootstrap die Dateien automatisch aktuell
 * (config_sync()), sobald sich Werte aus dem config-Block geändert haben.
 */

declare(strict_types=1);

// Fallback-Definition: data.php bindet uns ein und liefert den robusten Leser,
// aber im CLI-Direktaufruf (php php/config.php --gen) existiert er hier evtl.
// noch nicht – dann definieren wir eine lokale, identische Variante.
if (!function_exists('sf_read_json_file')) {
    function sf_read_json_file(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            return null;
        }
        $src = ltrim($raw);
        if (strpos($src, '/*') === 0) {
            $end = strpos($src, '*/');
            if ($end !== false) {
                $src = ltrim(substr($src, $end + 2));
            }
        }
        $decoded = json_decode($src, true);
        return is_array($decoded) ? $decoded : null;
    }
}

/**
 * Liest einen Wert aus data/site.json (direkt, ohne data.php, um
 * Zirkularität zu vermeiden). Liefert $default, wenn nicht vorhanden.
 */
function config_site_value(string $path, string $default = ''): string
{
    static $data = null;
    if ($data === null) {
        $file = dirname(__DIR__) . '/data/site.json';
        $data = is_file($file) ? (sf_read_json_file($file) ?? []) : [];
    }
    $cur = $data;
    foreach (explode('.', $path) as $key) {
        if (is_array($cur) && array_key_exists($key, $cur)) {
            $cur = $cur[$key];
        } else {
            return $default;
        }
    }
    return is_scalar($cur) ? (string) $cur : $default;
}

/**
 * ZENTRALE PROJEKT-KONFIGURATION.
 *
 * Alle Werte werden LIVE aus data/site.json → top-level "config"-Block gelesen
 * (Name, Domain, E-Mail, Telefon sowie die Build-Werte themeColor, assetsBase
 * und adminPath). So gibt es eine EINZIGE Quelle für alle projektbezogenen
 * Werte – sie stehen "ganz oben" in site.json.
 */
function project_config(): array
{
    return [
        'project' => [
            'name'        => config_site_value('config.name', ''),
            'domain'      => config_site_value('config.domain', ''),
            'email'       => config_site_value('config.email', ''),
            'phone'       => config_site_value('config.phone', ''),
            'phoneSchema' => config_site_value('config.phoneSchema', ''),
            'fax'         => config_site_value('config.fax', ''),
            'faxSchema'   => config_site_value('config.faxSchema', ''),
            'themeColor'  => config_site_value('config.themeColor', '#3D6490'),
            'assetsBase'  => config_site_value('config.assetsBase', '/assets'),
            'logo'        => config_site_value('config.logo', 'site/logo.png'),
            'port'        => config_site_value('config.port', '9999'),
            'adminPath'   => config_site_value('config.adminPath', '/admin'),
        ],
    ];
}

/**
 * Erzeugt aus einem beliebigen String einen npm-kompatiblen Slug (nur
 * [a-z0-9-], Umlaute/Leerzeichen/Sonderzeichen entfernt).
 */
function config_make_slug(string $s): string
{
    $s = strtolower($s);
    $replace = [
        'ä' => 'a',
        'ö' => 'o',
        'ü' => 'u',
        'ß' => 'ss',
        'à' => 'a',
        'á' => 'a',
        'â' => 'a',
        'é' => 'e',
        'è' => 'e',
        'ê' => 'e',
        'í' => 'i',
        'ì' => 'i',
        'î' => 'i',
        'ó' => 'o',
        'ò' => 'o',
        'ô' => 'o',
        'ú' => 'u',
        'ù' => 'u',
        'û' => 'u',
        'ç' => 'c',
    ];
    $s = strtr($s, $replace);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim($s, '-');
    return $s !== '' ? $s : 'site';
}

/** Liefert den konkreten, überall per %{ENV:ADMIN_PATH} referenzierten Admin-Pfad. */
function config_admin_path(): string
{
    $cfg = project_config();
    return rtrim($cfg['project']['adminPath'] ?? '/admin', '/');
}

/**
 * Generiert den Apache-Block für die .htaccess. Apache kann PHP-/Config-Werte
 * NICHT zur Laufzeit auflösen, daher wird der fertige Admin-Pfad eingesetzt.
 */
/**
 * Baut den fertigen htaccess-Block für den .htaccess (exakt eine Leerzeile
 * Puffer davor und danach, keine doppelten Newlines – daher idempotent).
 */
function config_htaccess_block(string $admin): string
{
    $inner = sprintf(
        '# >>> PROJECT ADMIN (generiert aus php/config.php) >>>' . "\n" .
            'SetEnvIf Request_URI ".*" ADMIN_PATH=%s' . "\n\n" .
            '# Alte Admin-URL (/admin/) auf das neue Admin weiterleiten' . "\n" .
            'RewriteCond %%{ENV:ADMIN_PATH} ^(/[^/]+)$' . "\n" .
            'RewriteRule ^admin(/.*)?$ %%1/ [R=301,L]' . "\n\n" .
            '# Admin-includes nie direkt aufrufen' . "\n" .
            '<IfModule mod_rewrite.c>' . "\n" .
            '  RewriteRule ^php/.*\.php$ - [F]' . "\n" .
            '  RewriteCond %%{ENV:ADMIN_PATH} ^(/[^/]+)$' . "\n" .
            '  RewriteRule ^%%1/includes/.*\.php$ - [F]' . "\n" .
            '</IfModule>' . "\n" .
            '# <<< PROJECT ADMIN (generiert aus php/config.php) <<<',
        $admin
    );
    // sprintf-Reste nach %% ersetzen (%%->% , %1->%1)
    $inner = str_replace('%%1', '%1', $inner);
    return "\n" . $inner . "\n";
}

/**
 * Fügt den htaccess-Block idempotent in die .htaccess ein (Marker-Ersetzung
 * bzw. Platzierung nach "RewriteEngine On") und normalisiert die Leerzeilen,
 * damit sich der Inhalt bei jedem Lauf nicht weiter aufbläht.
 */
function config_htaccess_insert(string $src, string $block): string
{
    $markerStart = '# >>> PROJECT ADMIN (generiert aus php/config.php) >>>';
    $markerEnd = '# <<< PROJECT ADMIN (generiert aus php/config.php) <<<';

    if (preg_match('/' . preg_quote($markerStart, '/') . '.*?' . preg_quote($markerEnd, '/') . '/s', $src)) {
        // Vorhandenen Block ersetzen und Zeilen-Nachbarn kollabieren: wir setzen
        // den Block mit genau einer Leerzeile Puffer, indem wir direkt davor
        // und danach eventuelle Leerzeilen auf eine reduzieren.
        $src = preg_replace(
            '/[ \t]*\n?(?:[ \t]*\n)*' . preg_quote($markerStart, '/') . '.*?' . preg_quote($markerEnd, '/') . '[ \t]*\n?(?:[ \t]*\n)*/s',
            "\n" . $block . "\n",
            $src,
            1
        );
        // Doppelte Leerzeilen global kollabieren, die durch Puffer entstehen könnten.
        $src = preg_replace('/\n{3,}/', "\n\n", $src);
        return $src;
    }

    if (strpos($src, 'RewriteEngine On') !== false) {
        $src = preg_replace(
            '/RewriteEngine On[ \t]*\n?/',
            'RewriteEngine On' . "\n" . $block . "\n",
            $src,
            1
        );
        $src = preg_replace('/\n{3,}/', "\n\n", $src);
        return $src;
    }

    return $block . "\n" . $src;
}

/** Generiert den Inhalt von package.json aus dem config-Block. */
function config_package_json(array $cfg): string
{
    $name = config_make_slug($cfg['name'] ?? 'Schreinerei Frank') . '-root';
    $pkg = [
        'name'        => $name,
        'version'     => '2.0.0',
        'private'     => true,
        'description' => ($cfg['name'] ?? 'Firmenname xx') . " – PHP-Site. 'npm run serve' startet den lokalen PHP-Dev-Server (Home + Admin).",
        'scripts'     => [
            'serve' => 'bash dev/serve.sh',
            'start' => 'bash dev/serve.sh',
            'dev'   => 'bash dev/serve.sh',
        ],
    ];
    return json_encode($pkg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}

/**
 * Alle generierten Build-Dateien als [Pfad => Inhalt]. Ruft die jeweiligen
 * Generatoren auf; die .htaccess ist hier ein Sonderfall mit Marker-Inset.
 */
function config_build_files(array $cfg): array
{
    $root = dirname(__DIR__);
    $htaccess = $root . '/.htaccess';
    $src = '';
    if (is_file($htaccess)) {
        $read = file_get_contents($htaccess);
        $src = $read === false ? '' : $read;
    }
    $src = config_htaccess_insert($src, config_htaccess_block(config_admin_path()));
    // Hinweis (Astro-Template): package.json wird NICHT mehr autogeneriert –
    // sie ist im Repo fest (Astro dev/build/preview) und darf vom PHP-Runtime
    // nicht überschrieben werden.
    return [
        $htaccess => $src,
    ];
}

/**
 * Synchronisiert alle generierten Build-Dateien, sofern sich deren Inhalt
 * gegenüber dem aktuellen Stand geändert hat. Liefert die Anzahl geschriebener
 * Dateien (0 = alles aktuell). Dieser Aufruf ist idempotent und billig.
 */
function config_sync(): int
{
    // Astro-Template: .htaccess und package.json werden nicht mehr
    // dynamisch generiert – alle Änderungen laufen über Git/Build.
    return 0;
}

// CLI: --gen-htaccess (nur .htaccess) bzw. --gen (alle Build-Dateien).
$isCliDirect = (PHP_SAPI === 'cli')
    && isset($argv[0])
    && isset($argc)
    && realpath($argv[0]) === __FILE__;

if ($isCliDirect) {
    $cfg = project_config();
    $mode = $argv[1] ?? '--gen';
    $count = ($mode === '--gen-htaccess')
        ? config_sync() // regeneriert ggf. nur, was sich geändert hat
        : config_sync();
    fwrite(STDOUT, "$count Build-Datei(en) aktualisiert (aus " . ($cfg['project']['adminPath'] ?? '') . ").\n");
    exit(0);
}

// Als Include/Web-Kontext: Konfig-Array als require-Ergebnis liefern.
return project_config();
