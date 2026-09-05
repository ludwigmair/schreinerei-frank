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
 *   GET  api.php?action=sitejson&token=…   -> aktuelle site.json (Build-Workflow)
 *   POST api.php?action=publish&token=…    -> triggert GitHub repository_dispatch
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

/**
 * Token aus Query-Parameter oder Authorization-Header (Bearer) extrahieren und
 * gegen die Publish-Tokens konstant-zeitvergleichen. Gültig für sitejson/publish.
 */
function api_has_valid_token(): bool {
    $given = (string) ($_GET['token'] ?? '');
    if ($given === '') {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            $given = trim($m[1]);
        }
    }
    if ($given === '') {
        return false;
    }
    foreach ([adm_sitejson_token(), adm_publish_token()] as $expected) {
        if ($expected !== '' && hash_equals($expected, $given)) {
            return true;
        }
    }
    return false;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

// Token-geschützte Publish-Endpunkte – nutzbar ohne Admin-Session (Build-Workflow);
// zusätzlich ist "publish" für den eingeloggten Admin (Button im Editor) offen.
if ($action === 'sitejson' || $action === 'publish') {
    $allowed = api_has_valid_token() || ($action === 'publish' && adm_is_logged_in());
    if (!$allowed) {
        api_json(['ok' => false, 'error' => 'Ungültiger Token.'], 401);
    }
    if ($action === 'sitejson' && $method === 'GET') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(adm_load_content(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        exit;
    }
    if ($action === 'publish' && $method === 'POST') {
        $pat = adm_publish_token();
        if ($pat === '') {
            api_json(['ok' => false, 'error' => 'PUBLISH_TOKEN ist nicht konfiguriert.'], 500);
        }
        $repo = 'ludwigmair/schreinerei-frank';
        $payload = json_encode(['event_type' => 'publish']);
        $ch = curl_init('https://api.github.com/repos/' . $repo . '/dispatches');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $pat,
                'Accept: application/vnd.github+json',
                'Content-Type: application/json',
                'User-Agent: frank-adm-publish',
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($code >= 200 && $code < 300) {
            api_json(['ok' => true]);
        }
        api_json(['ok' => false, 'error' => 'GitHub-Dispatch fehlgeschlagen (HTTP ' . $code . ($err !== '' ? ', CURL: ' . $err : '') . '): ' . $resp], 502);
    }
    api_json(['ok' => false, 'error' => 'Unbekannte Aktion.'], 404);
}

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
