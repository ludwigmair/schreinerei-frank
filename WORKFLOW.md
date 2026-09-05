# WORKFLOW – Deploy per GitHub Actions (SFTP)

Diese Datei beschreibt den **exakten Deployment-Ablauf** der Website auf den
Hoster-Server. Ziel ist ein **vollautomatischer, nachvollziehbarer** Vorgang:

> **Merge `develop` → `staging` ⇒ automatischer SFTP-Upload zum Hoster.**

Nichts wird manuell auf den Server kopiert – der Git-Workflow ist die einzige
Quelle. Alle Zugangsdaten sind als **GitHub-Secrets** hinterlegt und liegen
**nie** im Repository.

> **Erster Deploy auf einen frischen Server:** Es genügt, einmalig die
> `backup.php` und den Ordner `backup/` (inkl. `.htaccess`) auf den Server zu
> legen – damit die automatische Sicherung vor dem ersten Upload greift. Alles
> Weitere übernimmt der Workflow beim ersten `develop → staging`-Merge.
>
> **Wichtig (Zielpfad):** Das SFTP-Login landet nicht im Web-Root, sondern im
> TYPO3-SSH-Home (`sshroot`). Der echte Web-Root ist der per DCP gemappte
> Zielordner der Subdomain – dieser absolute Pfad steht im Secret
> `FTP_TARGET_DIR` (z. B. `/home/www/doc/28707/typopublic.com/schreinerei-frank`),
> sonst wird in den falschen Ordner geschrieben.
---

## 1. Überblick über den Ablauf

```
[develop] --merge--> [staging] --push--> GitHub
                                          │
                                          ▼
                       GitHub Actions: "Deploy staging per SFTP"
                          │  (nur wenn entwickel→staging-Merge)
                          ▼
                    1) Sicherung anlegen (backup.php → backup/backup_<Datum>.zip)
                          ▼
                    2) SFTP-Upload zum Hoster (Port 22) – OHNE Löschen von
                       serverseitigen Uploads und OHNE .env zu berühren
```

Beim regelmäßigen Freigabe-Zyklus entsteht der Deploy also **zwangsläufig**:
Sobald `staging` per `git merge develop` aktualisiert und gepusht wird, lädt die
Action den aktuellen Stand hoch. **Vor jedem Upload wird der aktuelle
Server-Stand als datierte Zip unter `backup/` gesichert** – so kann bei einem
fehlerhaften Deploy jederzeit der vorherige Zustand zurückgeholt werden.
Kein weiterer manueller Schritt nötig.

---

## 2. Der Workflow (`deploy-staging.yml`)

**Datei:** `.github/workflows/deploy-staging.yml`

| Trigger | Verhalten |
| --- | --- |
| `push` auf `staging` | Startet den Workflow |
| Commit ist **kein** Merge | **Abbruch** – wird nicht deployed |
| Commit **ist** ein Merge, Elternteil **nicht** `origin/develop` | **Abbruch** – wird nicht deployed |
| Commit **ist** ein Merge mit Elternteil `origin/develop` | **SFTP-Deploy** wird ausgeführt |

D.h. nur ein echter **`develop → staging`-Merge** löst den Upload aus; lose
Staging-Commits oder ein Merge aus einem anderen Branch werden ignoriert.

**Deployed wird:**

- `index.php`, `php/`, `frank-adm/`, `data/`, `assets/`, `datenschutz/`,
  `impressum/`, `.htaccess`, `favicon.ico`, `robots.php`, `sitemap.php`,
  `webmanifest.php`, `llms.php` … (alle Web-Dateien)

**Ausgeschlossen (nicht hochgeladen):**

- `.git` (Repository-Interna)
- `dev/` (lokale Dev-Tools: `serve.sh`, `router.php`, Logs)
- `node_modules/`, `.DS_Store`, `server.log`, `srv_t.log`
- **`.env`** (niemals hochladen/überschreiben)
- **`backup/`** (der Sicherungsordner mit den Zips)
- `data/admin.local.json` (lokale Admin-Zugangsdaten)

Damit landen nur Dateien auf dem Server, die für den Live-Betrieb nötig sind –
und keinerlei Geheimnisse.

> **Sicherheits-Grundsatz (wichtig für Admin-Uploads):**
> Der Workflow verwendet **bewusst KEIN `delete-remote`**. Dadurch werden
> **serverseitig hochgeladene Bilder** (z. B. über den Frank-Adm-Upload nach
> `assets/`) sowie `.env` **niemals gelöscht** – es werden nur neue/geänderte
> Dateien aus dem Repo hochgeladen. Ein versehentliches Löschen von
> Server-Daten ist damit ausgeschlossen.

### Werkzeug

Der Upload nutzt die SFTP-fähige Action
[`wangyucode/sftp-upload-action`@v3](https://github.com/wangyucode/sftp-upload-action)
(Passwort-Login, `exclude`-Patterns, inkrementelle Uploads via Hash-Datei).

> **Wichtig:** Diese Action deployt über **SFTP (SSH, Port 22)** – wie es
> typopublic anbietet und FileZilla nutzt. Sie lädt nur **hinzu/aktualisiert**
> unveränderte Dateien nicht erneut (kein `delete`), sodass serverseitige
> Uploads und `.env` nie angetastet werden. Seit der Umstellung auf
> `forceUpload: false` werden nur geänderte Dateien übertragen; der
> Vergleichs-Hash bleibt in `.sftp_upload_action_hashes` auf dem Server.

---

## 3. Secrets einrichten (PFLICHT vor dem ersten Deploy)

Ohne diese Geheimnisse schlägt der Deploy-Schritt fehl. Einrichtungsort:

> **Repo → Settings → Secrets and variables → Actions → New repository secret**

FTP_SERVER: typopublic.com
FTP_USERNAME: a28707
FTP_PASSWORD: 3RK=9sFu
BACKUP_URL:  <https://schreinerei-frank.typopublic.com/backup.php>
BACKUP_TOKEN: 4563
FTP_TARGET_DIR  /home/www/doc/28707/typopublic.com/schreinerei-frank

| Secret-Name | Bedeutung | Beispiel |
| --- | --- | --- |
| `FTP_SERVER` | SFTP-Host des Hosters (ohne Protokoll, ohne Pfad) | `ftp.typopublic.com` |
| `FTP_USERNAME` | FTP-Benutzer | `schreinerei` |
| `FTP_PASSWORD` | FTP-Passwort | (geheim) |
| `FTP_TARGET_DIR` | **Absoluter Zielpfad** auf dem Server (das SFTP-Login landet NICHTS im Web-Root, sondern im TYPO3-SSH-Home `sshroot` – der Web-Root der Subdomain ist dieser DCP-gemappte Ordner) | `/home/www/doc/28707/typopublic.com/schreinerei-frank` |
| `BACKUP_TOKEN` | **Neu** – Secret-Token der `backup.php`, das den Sicherungs-Aufruf absichert (muss dem `BACKUP_TOKEN` in `backup.php` entsprechen) | (geheim, selbst gewählt) |
| `BACKUP_URL` | **Neu** – vollständige HTTPS-URL der `backup.php` auf dem Server, **inkl. Subdomain/Unterordner**. Wichtig, weil die Site im Unterordner liegt (`https://FTP_SERVER/backup.php` wäre falsch) | `https://schreinerei-frank.typopublic.com/backup.php` |

> **Hinweise:**
>
> - `FTP_TARGET_DIR` ist **verpflichtend** auf den absoluten Web-Root-Pfad zu
>   setzen (siehe §1/Tabelle oben) – ohne ihn würde der Deploy im SFTP-Login-
>   Home (`sshroot`) landen und die Site bliebe unverändert.
> - Falls dein Hoster andere Zugangsdaten (z. B. separates FTP-Konto pro
>   Verzeichnis) liefert, einfach die Secrets entsprechend auffüllen.
> - Secrets lassen sich nach dem Anlegen **nicht mehr anzeigen** – nur neu
>   setzen. Sie sind beim Workflow-Run nur für berechtigte Ausführende
>   (**${{ secrets.* }}**) verfügbar und landen nie in einem Log.

---

## 4. Die Secrets im Workflow nutzen

Im Workflow werden die Secrets **einmal** in `env` geladen und dann an die
Deploy-Action übergeben. Auf diese Weise erscheinen sie nicht als komplette
Werte im Schritt-Log, sondern bleiben verborgen:

```yaml
env:
  FTP_SERVER:     ${{ secrets.FTP_SERVER }}
  FTP_USERNAME:   ${{ secrets.FTP_USERNAME }}
  FTP_PASSWORD:   ${{ secrets.FTP_PASSWORD }}
  FTP_TARGET_DIR: ${{ secrets.FTP_TARGET_DIR }}
  BACKUP_URL:     ${{ secrets.BACKUP_URL }}
  BACKUP_TOKEN:   ${{ secrets.BACKUP_TOKEN }}
```

```yaml
- uses: wangyucode/sftp-upload-action@v3
  with:
    host: ${{ env.FTP_SERVER }}
    username: ${{ env.FTP_USERNAME }}
    password: ${{ env.FTP_PASSWORD }}
    port: 22
    localDir: '.'
    remoteDir: ${{ env.FTP_TARGET_DIR }}   # absoluter Web-Root-Pfad
    forceUpload: false                     # inkrementelle Uploads
    concurrency: 4
    exclude: |
      .git, .github/**, *.md, node_modules/**, .env, dev/**,
      backup/*.zip, data/admin.local.json, package.json, *.log
```

---

## 5. Freigabe-Workflow (so führst du den Deploy aus)

### Voraussetzungen

- Aktueller `develop`-Stand ist lokal vorhanden, gepusht und die Änderungen sind
  in einem Commit auf `develop` enthalten.
- Die **Secrets** sind gesetzt (siehe §3).

### Schritt für Schritt

```bash
# 1) (optional) sicherstellen, dass develop aktuell ist
git checkout develop
git pull origin develop

# 2) staging auf den neuen Stand bringen
git checkout staging
git pull origin staging
git merge develop

# 3) pushen – DAS löst die Deploy-Action aus
git push origin staging
```

Nach Schritt 3 startet GitHub die Action. Sie prüft, ob es sich um einen
`develop → staging`-Merge handelt, und lädt dann per SFTP hoch.

### Ergebnis prüfen

- **GitHub:** Repo → **Actions** → Workflow „Deploy staging per SFTP" → letzter
  Run sollte `success` zeigen.
- **Server:** Dateien per FTP-Client prüfen oder die Website aufrufen.
- Status-Fehler beseitigen: Siehe §8.

---

## 6. Backups & Rollback

### Wo liegen die Sicherungen?

- Auf dem **Server** unter `backup/backup_<JJJJMMTT_HHMMSS>.zip`.
- Der Ordner `backup/` ist per `.htaccess` gegen öffentlichen Zugriff
  geschützt (nur die Zip-Dateien sind gesperrt). Er wird vom Deploy **nie**
  gelöscht und **nie** überschrieben.
- Enthalten ist der komplette Web-Root **ohne** `.env`, `.git`, `dev/`,
  `node_modules/`, Logs und ohne `backup/` selbst.

### Retention

- Es werden die **letzten 5** datierten Zips aufbewahrt; ältere werden beim
  nächsten Backup automatisch entfernt.
- Das steht über `BACKUP_KEEP` in `backup.php`.

### Manuelles Rollback (bei fehlerhaftem Deploy)

1. Per SFTP (Port 22) die gewünschte Zip `backup/backup_<Datum>.zip`
   herunterladen.
2. Lokal entpacken und den Inhalt **bis auf `.env`** per SFTP zurück auf den
   Server kopieren (`schreinerei-frank/` bzw. `FTP_TARGET_DIR`).
   **`.env` wird dabei niemals überschrieben** – sie bleibt unangetastet.
3. Optional: Die fehlerhafte Version per neuem `develop → staging`-Merge
   korrigieren und erneut deployen.

> **Hinweis:** Da jede Deploy-Version zusätzlich im Git (Staging-Branch)
> versioniert ist, ist auch `git revert` + erneutes Deployen ein sauberer
> Rollback-Weg – die Zip-Sicherung ist das zusätzliche Safety-Net für
> serverseitige (nicht-Repo-)Daten.

---

## 7. Was passiert, wenn der Workflow NICHT deployed

Falls die Action startet, aber nichts hochlädt, ist das in der Regel gewollt:

- Der Push auf `staging` war **kein Merge**, sondern ein direkter Commit
  (z. B. ein Hotfix direkt auf staging). → Kontrollierter Abbruch.
- Der Push war zwar ein Merge, aber **nicht von `develop`**.

> **Möchtest du auch direkte Staging-Commits deployen?** Dann ist der
> `check-merge`-Schritt im Workflow zu vereinfachen bzw. zu entfernen:
> Einfach die Schritte `check-merge` und die Bedingung
> `if: steps.check-merge.outputs.deployed == 'true'` löschen – dann wird jeder
> Push auf `staging` hochgeladen.

---

## 8. Fehlerbehebung

| Symptom | Mögliche Ursache | Gegenmaßnahme |
| --- | --- | --- |
| „Secrets nicht gefunden" / Auth-Fehler | Secrets fehlen oder falsch benannt | §3 prüfen, Secrets neu setzen |
| „Login/Connection failed" | `FTP_SERVER` falsch oder Hoster blockt unbekannte IP | Server-/Host-Anmeldedaten prüfen; ggf. FTP-Server/Firewall |
| Forget „permission denied" | `FTP_TARGET_DIR` existiert nicht | Server-Verzeichnis prüfen, korrekten Zielpfad setzen |
| Workflow läuft, aber deployt nicht | Kein Merge-Commit bzw. falsche Basis | §7 lesen |
| „protocol: invalid parameter – sftp" bei FTPS-Action | Action unterstützt nur FTP/FTPS | SFTP-fähige Action nutzen (`wangyucode/sftp-upload-action`, siehe §2) |

> **Sicherheit:** Zugangsdaten gehören **ausschließlich** in Secrets. Niemals
> in `site.json`, `PROJECT.md`, Commit-Messages oder Workflow-Dateien ablegen.

---

## 9. Admin-Zugänge lokal verwalten (`dev/users.php`)

Die Website hält ihre Admin-Logins als SHA-256-Hash-Map in `data/admin.json`
(versioniert, wird gedeployed) – oder für Produktions-Passwörter in
`data/admin.local.json` (unversioniert, **nicht** deployed, serverseitig).
Zur Verwaltung gibt es ein **lokales CLI-Tool**:

**Datei:** `dev/users.php` (bewusst **unversioniert**, steht im `.gitignore`)

> **Wichtig:** `dev/users.php` ist ein reines **Kommandozeilen-Werkzeug** –
> es ist **kein Web-Endpunkt**. Ein Browser-Aufruf wie
> `http://localhost:10000/dev/users.php` ist **nicht** vorgesehen und wäre
> unsicher (jeder mit HTTP-Zugriff könnte User anlegen). Nutzung nur über
> das Terminal im Projekt-Root:

```bash
php dev/users.php add stefan demo123      # User anlegen (Passwort auch interaktiv)
php dev/users.php setpw stefan neu123     # Passwort ändern
php dev/users.php remove stefan           # User löschen
php dev/users.php list                    # Alle User auflisten
php dev/users.php hash deinpasswort       # Nur SHA-256-Hash ausgeben
php dev/users.php --json data/admin.local.json add frank geheim  # alternative Datei
```

**Verhalten:**

- Nutername wird kleingeschrieben und der Hash exakt wie die Login-Prüfung
  (`frank-adm/includes/helpers.php`) erzeugt: `hash('sha256', $passwort)`.
- `add`/`setpw`/`remove` schreiben die User in `data/admin.json` und spiegeln
  **alle** User als `ADMIN_USERS='{...}'` in die lokale `.env` (nur
  Gedächtnisstütze – wird von keinem PHP-Code gelesen).
- Für **echte Produktions-Passwörter** gehört der Zugang serverseitig in
  `data/admin.local.json` (nie committen, nie deployen) – die Default-Accounts
  aus `data/admin.json` (`lmair`, `stefan` …) sind Entwicklungs-Zugänge.

---

## 10. Kurzreferenz

- Workflow-Datei: `.github/workflows/deploy-staging.yml`
- Secrets: `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_TARGET_DIR` (absoluter Web-Root, Pflicht), `BACKUP_TOKEN`, `BACKUP_URL`
- Backup: vor jedem Deploy automatisch `backup.php`, Ziel `backup/`, Retention letzte 5
- Wahrer Auslöser: **Merge `develop` → `staging`** und Push
- Protokoll: **SFTP, Port 22**
- Zielverzeichnis: **absoluter Web-Root-Pfad** aus `FTP_TARGET_DIR`
  (z. B. `/home/www/doc/28707/typopublic.com/schreinerei-frank`) – das
  SFTP-Login landet sonst im SSH-Home
