<?php

declare(strict_types=1);

// Kontakt-Endpunkt: nimmt die Formular-Felder (name, email, phone, message,
// Honeypot "website"), validiert serverseitig wie das Frontend und verschickt
// die Anfrage an den Empfänger aus der Config. Versandweg: SMTP, wenn in
// site.json unter "contact.mailer" ein Host konfiguriert ist, sonst PHP mail().
// - fetch (JS) wünscht JSON -> JSON-Antwort
// - klassischer POST (ohne JS) -> kompakte HTML-Bestätigungsseite

const KONTAKT_EINGABE_FELDER = ['name', 'email', 'phone', 'message'];

function kontakt_site(): array
{
    static $site = null;
    if ($site === null) {
        if (function_exists('site_load')) {
            $site = site_load();
        } else {
            $file = dirname(__DIR__) . '/data/site.json';
            $site = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?? []) : [];
        }
    }
    return $site;
}

function kontakt_config(): array
{
    if (!function_exists('project_config')) {
        $configFile = dirname(__DIR__) . '/php/config.php';
        if (is_file($configFile)) {
            require $configFile;
        }
    }
    $project = function_exists('project_config') ? (project_config()['project'] ?? []) : [];
    $site = kontakt_site();
    $contact = $site['contact'] ?? [];
    $mailTo = trim((string) ($contact['mailTo'] ?? ''));

    // Lokaler Test-Override: data/kontakt.local.json (NIE versioniert/deployed).
    // Erlaubt Empfänger + SMTP-Mailer nur für die lokale Entwicklung.
    $localFile = dirname(__DIR__) . '/data/kontakt.local.json';
    $local = is_file($localFile) ? (json_decode((string) file_get_contents($localFile), true) ?? []) : [];
    if (is_array($local)) {
        $localTo = trim((string) ($local['to'] ?? ''));
        if ($localTo !== '') {
            $mailTo = $localTo;
        }
        if (is_array($local['mailer'] ?? null)) {
            $contact['mailer'] = $local['mailer'];
        }
    }

    return [
        'to'       => $mailTo !== '' ? $mailTo : ($project['email'] ?? ''),
        'domain'   => preg_replace('#^https?://#i', '', $project['domain'] ?? ''),
        'siteName' => $project['name'] ?? '',
        'mailer'   => is_array($contact['mailer'] ?? null) ? $contact['mailer'] : [],
    ];
}

function kontakt_expects_json(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return strpos($accept, 'application/json') !== false;
}

function kontakt_respond_json(int $code, array $payload): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function kontakt_respond_html(int $code, string $title, string $text): never
{
    http_response_code($code);
    header('Content-Type: text/html; charset=utf-8');
    $config = kontakt_config();
    $home = $config['domain'] !== '' ? 'https://' . $config['domain'] . '/' : '/';
    echo '<!doctype html><html lang="de"><meta charset="utf-8">'
        . '<meta name="robots" content="noindex"><title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>'
        . '<body style="font-family:sans-serif;max-width:34rem;margin:4rem auto;line-height:1.6">'
        . '<h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>'
        . '<p>' . nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')) . '</p>'
        . '<a href="' . htmlspecialchars($home, ENT_QUOTES, 'UTF-8') . '">Zurück zur Startseite</a></body></html>';
    exit;
}

function kontakt_value(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function kontakt_valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL)
        && preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9._%+-]*[A-Za-z0-9])?@[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?(?:\.[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?)+$/', $email) === 1;
}

function kontakt_valid_phone(string $phone): bool
{
    $digits = preg_replace('/[\s()\/\-\.]+/', '', $phone);
    if (strlen($digits) < 9 || strlen($digits) > 15 || !preg_match('/^[\+]?\d+$/', $digits)) {
        return false;
    }
    return $digits[0] === '+'
        ? (bool) preg_match('/^\+\d{8,14}$/', $digits)
        : (bool) preg_match('/^(?:\d{7,14}|0\d{7,14}|00\d{7,14}|49\d{7,14}|0049\d{7,14})$/', $digits);
}

function kontakt_validate(array $values): array
{
    $errors = [];

    $name = $values['name'];
    if ($name === '') {
        $errors['name'] = 'Bitte geben Sie Ihren Namen an.';
    } elseif (mb_strlen($name) < 2 || preg_match('/\d/', $name)) {
        $errors['name'] = 'Bitte geben Sie einen gültigen Namen ohne Zahlen ein.';
    }

    $email = $values['email'];
    if ($email === '' || !kontakt_valid_email($email)) {
        $errors['email'] = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
    }

    $phone = $values['phone'];
    if ($phone !== '' && !kontakt_valid_phone($phone)) {
        $errors['phone'] = 'Bitte geben Sie eine gültige Telefonnummer ein.';
    }

    $message = $values['message'];
    if (mb_strlen($message) < 5) {
        $errors['message'] = 'Bitte beschreiben Sie Ihr Vorhaben kurz.';
    }

    return $errors;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Nur POST erlaubt.';
    exit;
}

if (kontakt_value('website') !== '') {
    if (kontakt_expects_json()) {
        kontakt_respond_json(200, ['ok' => true, 'status' => 'sent']);
    }
    kontakt_respond_html(200, 'Vielen Dank', 'Ihre Nachricht wurde übermittelt.');
}

$config = kontakt_config();
if ($config['to'] === '') {
    if (kontakt_expects_json()) {
        kontakt_respond_json(500, ['ok' => false, 'status' => 'error', 'error' => 'Empfänger nicht konfiguriert.']);
    }
    kontakt_respond_html(500, 'Fehler', 'Das Formular ist derzeit nicht erreichbar. Bitte rufen Sie uns an.');
}

$values = [];
foreach (KONTAKT_EINGABE_FELDER as $field) {
    $values[$field] = kontakt_value($field);
}

$errors = kontakt_validate($values);
if ($errors !== []) {
    if (kontakt_expects_json()) {
        kontakt_respond_json(422, ['ok' => false, 'status' => 'validation', 'errors' => $errors]);
    }
    $text = 'Bitte prüfen Sie Ihre Eingaben und versuchen Sie es erneut.';
    kontakt_respond_html(422, 'Eingabe unvollständig', $text);
}

$name = $values['name'];
$email = $values['email'];
$phone = $values['phone'];
$message = $values['message'];

$line = $config['siteName'] !== '' ? $config['siteName'] : 'Schreinerei Frank';
$subject = 'Kontaktanfrage von ' . $name . ' über die Website';

$body  = "Name: $name\n";
$body .= "E-Mail: $email\n";
if ($phone !== '') {
    $body .= "Telefon: $phone\n";
}
$body .= "\nNachricht:\n$message\n";

$from = 'noreply@' . ($config['domain'] !== '' ? $config['domain'] : 'localhost');
$headers = [
    'From: ' . $line . ' <' . $from . '>',
    'Reply-To: ' . $name . ' <' . $email . '>',
    'Content-Type: text/plain; charset=utf-8',
    'X-Mailer: PHP/' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
];

$sent = @mail($config['to'], $subject, $body, implode("\r\n", $headers));

if ($sent) {
    if (kontakt_expects_json()) {
        kontakt_respond_json(200, ['ok' => true, 'status' => 'sent']);
    }
    kontakt_respond_html(200, 'Vielen Dank', 'Ihre Nachricht wurde übermittelt. Wir melden uns in Kürze bei Ihnen.');
}

if (kontakt_expects_json()) {
    kontakt_respond_json(500, ['ok' => false, 'status' => 'error', 'error' => 'Senden fehlgeschlagen.']);
}
kontakt_respond_html(500, 'Fehler', 'Senden fehlgeschlagen. Bitte rufen Sie uns an oder schreiben Sie uns eine E-Mail.');