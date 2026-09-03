<?php
/**
 * Gemeinsames Bootstrap für alle Seiten.
 * Lädt Helfer + Daten einmalig, damit Partial-Requires einfach bleiben.
 */
declare(strict_types=1);

require_once __DIR__ . '/data.php';
require_once __DIR__ . '/seo.php';

if (!defined('SF_BOOTSTRAPPED')) {
    define('SF_BOOTSTRAPPED', true);
}

$s = site_load();
$ui = $s['ui'] ?? [];
