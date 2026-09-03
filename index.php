<?php

/**
 * Firmenname xxx – Router (Startseite + optionale Seiten).
 *
 * Ist lediglich ein Router: alle Templates liegen als .php-Partials in /php/
 * und werden hier nur eingebunden. Die URL bestimmt, welches Partial gerendert
 * wird. Saubere Pfade wie /impressum/ funktionieren über die physischen
 * Unterordner (impressum/index.php, datenschutz/index.php), die denselben
 * Bootstrap nutzen.
 */

declare(strict_types=1);

require_once __DIR__ . '/php/bootstrap.php';

// Router-Kandidat (z. B. /impressum) – wird genutzt, wenn dieser Router ohne
// physischen Unterordner direkt angesprochen wird (z. B. per Rewrite).
$page = '';
if (isset($_GET['page'])) {
    $target = trim((string) $_GET['page'], '/');
    if (in_array($target, ['impressum', 'datenschutz'], true)) {
        $page = $target;
    }
}

$page = $page ?? '';
$baseHref = './';
include __DIR__ . '/php/header.php';

if ($page === 'impressum' || $page === 'datenschutz') {
    include __DIR__ . '/php/legal.php';
} else {
    include __DIR__ . '/php/home.php';
}

include __DIR__ . '/php/footer.php';
