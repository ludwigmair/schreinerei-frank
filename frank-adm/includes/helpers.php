<?php
/**
 * Frank-Adm – Helfer: Sitzung, Authentifizierung, Content-Lese/Schreib-Zugriff.
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    // HttpOnly + SameSite erhöhen Sicherheit (nur über HTTPS gonnen SameSite=None).
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function adm_root(): string {
    // helpers.php liegt unter frank-adm/includes/ -> Projekt-Root ist 2 Ebenen höher.
    return dirname(__DIR__, 2);
}

/**
 * Liest einen geheimen Wert aus der (nicht versionierten) .env-Datei im Root.
 * Format: NAME=wert – eine Zeile je Eintrag, '#'-Zeilen sind Kommentare.
 */
function adm_env(string $name): string {
    static $vars = null;
    if ($vars === null) {
        $vars = [];
        $path = adm_root() . '/.env';
        $raw = is_file($path) ? file_get_contents($path) : false;
        if ($raw !== false) {
            foreach (explode("\n", $raw) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') {
                    continue;
                }
                [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
                $vars[trim($key)] = trim($value);
            }
        }
    }
    return (string) ($vars[$name] ?? '');
}

/**
 * Token, mit dem der Build-Workflow die aktuelle site.json abrufen darf
 * (siehe .github/workflows/publish.yml). Konfiguriert via SITEJSON_TOKEN in .env.
 */
function adm_sitejson_token(): string {
    return adm_env('SITEJSON_TOKEN');
}

/**
 * Personal Access Token (Repo-Schreibrecht) zum Triggern des Publish-Workflows
 * über die GitHub repository_dispatch-API. Konfiguriert via PUBLISH_TOKEN in .env.
 */
function adm_publish_token(): string {
    return adm_env('PUBLISH_TOKEN');
}

function adm_users_file(): string {
    // Produktions-Zugangsdaten in einer NICHT versionierten Datei ablegen.
    $local = adm_root() . '/data/admin.local.json';
    if (is_file($local)) {
        return $local;
    }
    // Fallback auf ein optional versioniertes Beispieldatei (Standard-Login).
    return adm_root() . '/data/admin.json';
}

function adm_content_file(): string {
    return adm_root() . '/data/site.json';
}

function adm_users(): array {
    static $users = null;
    if ($users !== null) {
        return $users;
    }
    $users = [];
    $path = adm_users_file();
    if (is_file($path)) {
        $raw = file_get_contents($path);
        $decoded = $raw !== false ? json_decode($raw, true) : null;
        if (is_array($decoded)) {
            $users = $decoded;
        }
    }
    return $users;
}

function adm_is_logged_in(): bool {
    return !empty($_SESSION['sf_admin_auth']);
}

function adm_require_login(): void {
    if (!adm_is_logged_in()) {
        header('Location: index.php?view=login');
        exit;
    }
}

function adm_verify(string $username, string $password): bool {
    $users = adm_users();
    $user = strtolower(trim($username));
    if (!isset($users[$user])) {
        return false;
    }
    $hash = hash('sha256', $password);
    return hash_equals((string) $users[$user], $hash);
}

function adm_login(string $username): void {
    $_SESSION['sf_admin_auth'] = true;
    $_SESSION['sf_admin_user'] = $username;
    session_regenerate_id(true);
}

function adm_logout(): void {
    unset($_SESSION['sf_admin_auth'], $_SESSION['sf_admin_user']);
}

function adm_load_content(): array {
    $path = adm_content_file();
    if (!is_file($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    $data = $raw !== false ? json_decode($raw, true) : null;
    return is_array($data) ? $data : [];
}

/**
 * Liefert die zentralen config-Werte (aufgelöst), damit der Admin die
 * {config.X}-Platzhalter in Feldern als echte Werte darstellen kann.
 * Quelle ist php/config.php (project_config bzw. der config-Block).
 */
function adm_config_vars(): array {
    if (function_exists('project_config')) {
        return project_config()['project'] ?? [];
    }
    $configFile = adm_root() . '/php/config.php';
    if (is_file($configFile)) {
        $c = require_once $configFile;
        if (is_array($c) && isset($c['project'])) {
            return $c['project'];
        }
        if (is_array($c)) {
            return $c;
        }
    }
    return [];
}

/**
 * Speichert die Inhalte JSON-schön mit schreibbarem Unicode in data/site.json.
 * Liefert [bool $ok, string $error].
 */
function adm_save_content(array $data): array {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return [false, 'Inhalt konnte nicht als JSON serialisiert werden: ' . json_last_error_msg()];
    }
    $json .= "\n";
    $path = adm_content_file();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    // Atomar schreiben: Temp-Datei + rename verhindert halbgeschriebene Datei.
    $tmp = $path . '.tmp';
    if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
        return [false, 'Die Datei konnte nicht geschrieben werden (Rechte auf data/site.json prüfen).'];
    }
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return [false, 'Die Datei konnte nicht ersetzt werden (Rechte auf data/site.json prüfen).'];
    }
    @chmod($path, 0664);
    return [true, ''];
}
