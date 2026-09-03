<?php
/**
 * Frank-Adm – Inhalte-Editor (PHP-Frontend).
 *
 * Der Editor arbeitet direkt auf data/site.json über die PHP-API
 * (api.php). Änderungen werden per "Speichern" atomar in die Datei
 * geschrieben und sind sofort auf der öffentlichen Seite sichtbar.
 * Bild-Uploads landen serverseitig in assets/img/ (inkl. WebP-Variante).
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';

$isLoggedIn = adm_is_logged_in();
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Frank-Adm · Inhalte pflegen</title>
<link rel="stylesheet" href="assets/admin.css?v=3.1.0">
</head>
<body>
<div class="wrap">
  <a class="back" href="../index.php">← Zur Website</a>

  <!-- ===== Login ===== -->
  <div id="login-view" <?php echo $isLoggedIn ? 'hidden' : ''; ?>>
    <div class="login-card">
      <h2>Frank-Adm</h2>
      <p>Bitte mit Benutzer und Passwort anmelden.</p>
      <input type="text" id="user" placeholder="Benutzer" autocomplete="username">
      <input type="password" id="pw" placeholder="Passwort" autocomplete="current-password">
      <div class="banner err" id="login-err" hidden></div>
      <button class="btn" id="login-btn" type="button">Anmelden</button>
    </div>
  </div>

  <!-- ===== Editor ===== -->
  <div id="editor-view" <?php echo $isLoggedIn ? '' : 'hidden'; ?>>
    <header class="top">
      <div>
        <h1>Inhalte pflegen</h1>
        <div class="sub">Speichern schreibt direkt in <code>data/site.json</code> – sofort auf der Seite sichtbar.</div>
      </div>
      <button class="btn secondary" id="logout-btn" type="button">Abmelden</button>
    </header>

    <div class="nav-cards" id="nav-cards"></div>

    <div id="form-root" class="load-screen">Lade Inhalte …</div>

    <div class="toolbar">
      <span class="status" id="status">Bereit</span>
      <button class="btn secondary" id="reload-btn" type="button">Neu laden</button>
      <button class="btn" id="save-btn" type="button">Speichern</button>
    </div>
  </div>
</div>
<div id="toast" class="toast" hidden></div>

<script>
window.SF_ADMIN = { loggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?> };
</script>
<script src="assets/admin.js?v=3.1.0"></script>
</body>
</html>
