<?php

/**
 * Firmenname xxx – zentraler Datenzugriff (Single Source of Truth)
 *
 * Lädt data/site.json einmalig und stellt Hilfsfunktionen für das
 * serverseitige Rendering bereit. Alle editierbaren Inhalte (Texte,
 * Bilder, Slider, Galerie, SEO/Head-Infos) kommen aus dieser Datei.
 */

declare(strict_types=1);

function site_content_path(): string
{
    return dirname(__DIR__) . '/data/site.json';
}

/**
 * Ersetzt "{config.KEY}"-Platzhalter in einem Wert rekursiv durch die Werte
 * des top-level "config"-Blocks aus site.json. So können Duplikate in
 * site.json eliminiert werden (z. B. "email": "{config.email}").
 */
function site_resolve_config(array &$node, array $cfg): void
{
    foreach ($node as $k => &$v) {
        if (is_array($v)) {
            site_resolve_config($v, $cfg);
        } elseif (is_string($v)) {
            if (strpos($v, '{config.') !== false) {
                $v = preg_replace_callback('/\{config\.([a-zA-Z0-9_]+)\}/', static function ($m) use ($cfg) {
                    return $cfg[$m[1]] ?? $m[0];
                }, $v);
            }
        }
    }
    unset($v);
}

/**
 * Liest eine JSON-Datei tolerant ein: entfernt einen führenden
 * Kommentarblock (/* ... *\/ bzw. // ) und Fehlerquellen, damit auch
 * dateien mit dokumentierendem Kopf sicher gelesen werden.
 */
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
    // Führenden /* ... */ Kommentarblock entfernen (falls vorhanden).
    if (strpos($src, '/*') === 0) {
        $end = strpos($src, '*/');
        if ($end !== false) {
            $src = ltrim(substr($src, $end + 2));
        }
    }
    $decoded = json_decode($src, true);
    return is_array($decoded) ? $decoded : null;
}

function site_load(): array
{
    static $data = null;
    if ($data !== null) {
        return $data;
    }
    $path = site_content_path();
    $data = sf_read_json_file($path) ?? [];

    // Projekt-Konfiguration aus php/config.php (Single Source of Truth)
    // einblenden, damit PHP-Zugriffe über site_config() funktionieren und die
    // Werte nicht doppelt in site.json gepflegt werden müssen.
    $configFile = dirname(__DIR__) . '/php/config.php';
    if (is_file($configFile)) {
        $projConfig = require $configFile;
        if (is_array($projConfig) && isset($projConfig['project'])) {
            $data['config'] = $projConfig;
        }
    }

    // Der top-level "config"-Block in site.json ist die EINZIGE Quelle für
    // Firmenname/Domain/E-Mail/Telefon. Wir spiegeln die Werte in die
    // business/meta-Struktur, damit bestehende Aufrufe (business.name,
    // business.email, meta.siteUrl …) automatisch dieselben Werte liefern
    // und nirgendwo doppelt gepflegt wird.
    $cfg = $data['config']['project'] ?? [];
    foreach (
        [
            'business.name'  => $cfg['name']  ?? null,
            'business.phone' => $cfg['phone'] ?? null,
            'business.email' => $cfg['email'] ?? null,
            'meta.siteUrl'   => $cfg['domain'] ?? null,
        ] as $path => $val
    ) {
        if ($val === null) {
            continue;
        }
        $parts = explode('.', $path);
        // Ziel-Ref aufbauen
        $ref = &$data;
        foreach ($parts as $i => $key) {
            if (!isset($ref[$key]) || !is_array($ref[$key])) {
                $ref[$key] = [];
            }
            if ($i === count($parts) - 1) {
                $ref[$key] = $val;
            } else {
                $ref = &$ref[$key];
            }
        }
        unset($ref);
    }

    // Platzhalter {config.KEY} in allen Strings auflösen (Duplikat-Eliminierung).
    $resolveFrom = $data['config']['project'] ?? [];
    site_resolve_config($data, $resolveFrom);

    // Build-Dateien (.htaccess, site.webmanifest, package.json) automatisch
    // aktuell halten, sobald sich config-Werte geändert haben. Idempotent.
    if (function_exists('config_sync')) {
        config_sync();
    }

    return $data;
}

/** Sicherer Pfad-Zugriff: site_get($s, 'business.phone') */
function site_get(array $data, string $path, $default = null)
{
    $cur = $data;
    foreach (explode('.', $path) as $key) {
        if (is_array($cur) && array_key_exists($key, $cur)) {
            $cur = $cur[$key];
        } else {
            return $default;
        }
    }
    return $cur;
}

function site_get_str(array $data, string $path, string $default = ''): string
{
    $v = site_get($data, $path, $default);
    return is_scalar($v) ? (string) $v : $default;
}

function site_esc(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Liefert den relativen Pfad vom aktuellen Verzeichnis zur App-Wurzel. */
function site_rel_root(): string
{
    return $GLOBALS['SF_BASE_HREF'] ?? './';
}

/** Setzt die relative App-Basis (zugleich lokale $baseHref) global. */
function site_set_base(string $baseHref): string
{
    $GLOBALS['SF_BASE_HREF'] = $baseHref;
    return $baseHref;
}

/** Relative Basis der App (domainfrei, abgeleitet aus der Seiten-Basis). */
function site_base(): string
{
    return rtrim(site_rel_root(), '/');
}

/**
 * Interne URL: macht einen (root-absoluten) Pfad seitenrelativ und domainfrei.
 * Externe URLs (http, //) bleiben unverändert.
 */
function site_abs(string $path): string
{
    if ($path === '' || strpos($path, 'http') === 0 || strpos($path, '//') === 0) {
        return $path;
    }
    return rtrim(site_base(), '/') . '/' . ltrim($path, '/');
}

/** Anker-/ID-Pfad für interne Referenzen (z. B. "#organization") – domainfrei. */
function site_anchor(string $fragment): string
{
    return rtrim(site_base(), '/') . '/#' . ltrim($fragment, '#');
}

/** Projekt-Konfiguration aus dem zentralen config.block lesen. */
function site_config(array $s, string $path, string $default = ''): string
{
    return site_get_str($s, 'config.' . $path, $default);
}

/**
 * Zentrale Asset-Basis (z. B. /assets) mit Pfad kombinieren.
 * Domainfrei UND seitenrelativ, damit Bilder/Styles auch in einem
 * Unterordner-Deploy funktionieren (./assets/... bzw. ../assets/...).
 */
function site_asset(array $s, string $path): string
{
    if ($path === '' || strpos($path, 'http') === 0 || strpos($path, '//') === 0) {
        return $path;
    }
    $base = rtrim(site_config($s, 'project.assetsBase', '/assets'), '/');
    // Pfad enthält die Asset-Basis bereits (z. B. "/assets/content/...")?
    if ($base !== '' && ($path === $base || strpos($path, $base . '/') === 0)) {
        $full = $path;
    } else {
        $full = $base . '/' . ltrim($path, '/');
    }
    return rtrim(site_base(), '/') . '/' . ltrim($full, '/');
}

/**
 * Filtert eine Liste von Einträgen auf aktive herab: Einträge mit
 * explizitem "active" === false werden entfernt (für Slider, Galerie, FAQ…).
 * Einträge ohne "active"-Feld gelten als aktiv.
 */
function site_active_filter(array $items): array
{
    return array_values(array_filter(
        $items,
        static function ($item): bool {
            return !is_array($item) || (($item['active'] ?? true) !== false);
        }
    ));
}
