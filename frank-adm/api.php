<?php
/**
 * Frank-Adm – JSON-API.
 * Endpunkte:
 *   POST api.php?action=login        {user, password}
 *   POST api.php?action=logout
 *   GET  api.php?action=content      -> {content, users?}
 *   POST api.php?action=save         {content}  (geschützt)
 *   POST api.php?action=upload       multipart image (geschützt)
 *   GET  api.php?action=images       -> Liste assets/img (geschützt)
 *   GET  api.php?action=session      -> {loggedIn}
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/upload.php';

function api_json(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

if ($method === 'POST' && $action === 'login') {
    $body = json_decode(file_get_contents('php://input'), true);
    $user = trim((string) ($body['user'] ?? ''));
    $pass = (string) ($body['password'] ?? '');
    if ($user === '' || $pass === '' || !adm_verify($user, $pass)) {
        api_json(['ok' => false, 'error' => 'Benutzername oder Passwort falsch.'], 401);
    }
    adm_login($user);
    api_json(['ok' => true]);
}

if ($method === 'POST' && $action === 'logout') {
    adm_logout();
    api_json(['ok' => true]);
}

if ($action === 'session') {
    api_json(['loggedIn' => adm_is_logged_in()]);
}

// Ab hier: geschützte Endpunkte
if (!adm_is_logged_in()) {
    api_json(['ok' => false, 'error' => 'Nicht angemeldet.'], 401);
}

if ($method === 'GET' && $action === 'content') {
    api_json(['ok' => true, 'content' => adm_load_content(), 'config' => adm_config_vars()]);
}

if ($method === 'POST' && $action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true);
    $content = $body['content'] ?? null;
    if (!is_array($content)) {
        api_json(['ok' => false, 'error' => 'Kein gültiger Inhalt übermittelt.'], 400);
    }
    [$ok, $err] = adm_save_content($content);
    if (!$ok) {
        api_json(['ok' => false, 'error' => $err], 500);
    }
    api_json(['ok' => true]);
}

if ($method === 'POST' && $action === 'upload') {
    if (empty($_FILES['file'])) {
        api_json(['ok' => false, 'error' => 'Keine Datei übermittelt.'], 400);
    }
    [$ok, $result] = adm_handle_upload($_FILES['file']);
    if (!$ok) {
        api_json(['ok' => false, 'error' => $result], 400);
    }
    api_json(['ok' => true, 'path' => $result]);
}

if ($method === 'GET' && $action === 'images') {
    api_json(['ok' => true, 'images' => adm_image_list()]);
}

api_json(['ok' => false, 'error' => 'Unbekannte Aktion.'], 404);
