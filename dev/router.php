<?php
/**
 * Frank-Adm Link: startet den PHP-Dev-Server für lokale Entwicklung.
 * Dieser Router bildet die .htaccess-Regeln der Live-Umgebung nach
 * (saubere URLs für sitemap/robots/llms sowie Verzeichnisse).
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$uri  = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

require_once $root . '/php/data.php';
$s = site_load();
$adminPath = '/' . ltrim(site_config($s, 'project.adminPath', '/admin'), '/');

// Saubere PEM-Endpunkte
if ($uri === '/sitemap.xml')   { require $root . '/sitemap.php';   return true; }
if ($uri === '/robots.txt')    { require $root . '/robots.php';    return true; }
if ($uri === '/llms.txt')      { require $root . '/llms.php';      return true; }

// Alte Admin-URL (/admin/) auf den konfigurierten Admin-Pfad weiterleiten
if ($uri === '/admin' || $uri === '/admin/' || $uri === '/admin/index.php') {
    header('Location: ' . $adminPath . '/', true, 301);
    return true;
}

// Sensible Dateien blockieren
if ($uri === '/data/admin.json' || $uri === '/data/site.json') {
    http_response_code(403);
    return true;
}
if (strpos($uri, '/php/') === 0 || strpos($uri, $adminPath . '/includes/') === 0) {
    http_response_code(403);
    return true;
}

// Verzeichnis ohne Datei -> index.php
if ($uri !== '/' && !preg_match('/\.\w+$/', $uri)) {
    $candidate = $root . $uri . '/index.php';
    if (is_file($candidate)) {
        require $candidate;
        return true;
    }
}

// Standard: damit php -S statische Dateien selbst bedient, false zurückgeben
return false;
