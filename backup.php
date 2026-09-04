<?php
/**
 * backup.php – Erstellt eine datierte ZIP-Sicherung des Web-Roots auf dem Server.
 *
 * Wird per HTTP von der GitHub Action aufgerufen (vor jedem Deploy), z. B.:
 *   curl -fsS -X POST "https://STAGING-DOMAIN/backup.php" \
 *        -H "Authorization: Bearer <BACKUP_TOKEN>"
 *
 * Sicherheits- & Schutz-Regeln:
 *  - Es wird NUR bei gültigem Token ausgeführt (kein öffentlicher Zugriff).
 *  - Die ZIP liegt in backup/backup_<Datum>.zip (nicht öffentlich auslieferbar
 *    durch eine .htaccess-Sperre; siehe §Schutz).
 *  - Aus der ZIP ausgeschlossen: .env, .git, backup/ selbst, sowie gängige
 *    unerwünschte Dateien (Logs, .DS_Store, komplett local genutzte Hilfsordner).
 *  - Es werden höchstens die letzten BACKUP_KEEP (Default: 5) Zips behalten.
 */
declare(strict_types=1);

/** Token – muss identisch mit dem GitHub-Secret BACKUP_TOKEN sein. */
const BACKUP_TOKEN = 'CHANGE_ME_backup_token';

/** Anzahl zu behaltender Sicherungen (Retention). */
const BACKUP_KEEP = 5;

header('Content-Type: application/json; charset=utf-8');

/* ---------- Auth ---------- */
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
    $token = trim($m[1]);
} else {
    $token = $_POST['token'] ?? ($_GET['token'] ?? '');
}

if (!is_string($token) || !hash_equals(BACKUP_TOKEN, $token)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

/* ---------- Voraussetzungen ---------- */
if (!class_exists('ZipArchive')) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'ZipArchive nicht verfügbar']);
    exit;
}

/* ---------- Pfade ---------- */
$root   = realpath(__DIR__) ?: __DIR__;
$backup = rtrim($root, '/\\') . '/backup';
if (!is_dir($backup) && !mkdir($backup, 0775, true) && !is_dir($backup)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'backup/ nicht erstellbar']);
    exit;
}

$timestamp = date('Ymd_His');
$zipPath   = $backup . '/backup_' . $timestamp . '.zip';

/* ---------- Auszuschließende Pfade (relativ zu $root) ---------- */
function isExcluded(string $rel, string $base): bool {
    // Immer ausschließen: Geheimnisse, Repo-History, Backup-Ordner, Artefakte
    foreach ([
        '.env',
        '.git',
        'backup',
        'dev',
        'node_modules',
        '.DS_Store',
        'server.log',
        'srv_t.log',
        'Thumbs.db',
        '.htaccess.swp',
    ] as $needle) {
        if ($rel === $needle || strpos($rel, $needle . '/') === 0 || strpos($rel, '/' . $needle) !== false) {
            return true;
        }
    }
    return false;
}

/* ---------- ZIP aufbauen ---------- */
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Zip nicht erstellbar (Schreibrechte?)']);
    exit;
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$added = 0;
foreach ($iterator as $file) {
    /** @var SplFileInfo $file */
    $full = $file->getPathname();
    $rel  = substr($full, strlen($root) + 1);

    if (isExcluded($rel, $root)) {
        continue;
    }

    if ($file->isDir()) {
        $zip->addEmptyDir($rel);
    } elseif ($file->isFile()) {
        $local = substr($full, strlen($root) + 1);
        $zip->addFile($full, $local);
        $added++;
    }
}

$zip->close();

if ($added === 0) {
    // Nichts anzupacken? Trotzdem eine leere Sicherung anlegen, damit der
    // Deploy nicht fälschlich als "erfolgreich" abgebrochen wirkt.
}

/* ---------- Retention: nur die letzten BACKUP_KEEP Zips behalten ---------- */
$zips = glob($backup . '/backup_*.zip');
if ($zips !== false && count($zips) > BACKUP_KEEP) {
    usort($zips, 'strcmp'); // sortiert lexikalisch = chronologisch (Datum + Zeit)
    $toDelete = array_slice($zips, 0, count($zips) - BACKUP_KEEP);
    foreach ($toDelete as $old) {
        @unlink($old);
    }
}

echo json_encode([
    'ok'      => true,
    'file'    => 'backup/' . basename($zipPath),
    'files'   => $added,
    'kept'    => BACKUP_KEEP,
]);
exit;
