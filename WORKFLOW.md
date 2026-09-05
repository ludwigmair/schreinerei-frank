# WORKFLOW – Deploy per GitHub Actions (SFTP)

Diese Datei beschreibt den **exakten Deployment-Ablauf** der Website auf den
Hoster-Server. Ziel ist ein **vollautomatischer, nachvollziehbarer** Vorgang:

> **Merge `develop` → `staging` ⇒ automatischer SFTP-Upload zum Hoster.**

Nichts wird manuell auf den Server kopiert – der Git-Workflow ist die einzige
Quelle. Alle Zugangsdaten sind als **GitHub-Secrets** hinterlegt und liegen
**nie** im Repository.

Initial muss die site einmal via ftp deployed werden, damit spaeter die backup.php gefunden wird
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
[`Dylan700/sftp-upload-action`@v1.2.5](https://github.com/Dylan700/sftp-upload-action)
(Passwort-Login, `ignore`-Patterns, Upload-Zuordnung `./ → schreinerei-frank/`).

> **Wichtig:** Diese Action deployt über **SFTP (SSH, Port 22)** – wie es
> typopublic anbietet und FileZilla nutzt. Sie lädt nur **hinzu/aktualisiert**
> (kein `delete`), sodass serverseitige Uploads und `.env` nie angetastet
> werden.

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
| `FTP_TARGET_DIR` | **Optional** – Zielverzeichnis auf dem Server; Default ist `schreinerei-frank` | `schreinerei-frank` |
| `BACKUP_TOKEN` | **Neu** – Secret-Token der `backup.php`, das den Sicherungs-Aufruf absichert (muss dem `BACKUP_TOKEN` in `backup.php` entsprechen) | (geheim, selbst gewählt) |
| `BACKUP_URL` | **Neu** – vollständige HTTPS-URL der `backup.php` auf dem Server, **inkl. Subdomain/Unterordner**. Wichtig, weil die Site im Unterordner liegt (`https://FTP_SERVER/backup.php` wäre falsch) | `https://schreinerei-frank.typopublic.com/backup.php` |

> **Hinweise:**
>
> - Der Wert von `FTP_TARGET_DIR` wird im Workflow mit `'schreinerei-frank'` als
>   Default belegt: `${{ secrets.FTP_TARGET_DIR || 'schreinerei-frank' }}`.
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
  FTP_TARGET_DIR: ${{ secrets.FTP_TARGET_DIR || 'schreinerei-frank' }}
```

```yaml
with:
  server:   ${{ env.FTP_SERVER }}
  username: ${{ env.FTP_USERNAME }}
  password: ${{ env.FTP_PASSWORD }}
  port:     22
  uploads: |
    ./ => ./${{ env.FTP_TARGET_DIR }}/
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
| „protocol: invalid parameter – sftp" bei FTPS-Action | Action unterstützt nur FTP/FTPS | SFTP-fähige Action nutzen (`Dylan700/sftp-upload-action`, siehe §2) |

> **Sicherheit:** Zugangsdaten gehören **ausschließlich** in Secrets. Niemals
> in `site.json`, `PROJECT.md`, Commit-Messages oder Workflow-Dateien ablegen.

---

## 9. Kurzreferenz

- Workflow-Datei: `.github/workflows/deploy-staging.yml`
- Secrets: `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_TARGET_DIR` (opt.), `BACKUP_TOKEN`, `BACKUP_URL`
- Backup: vor jedem Deploy automatisch `backup.php`, Ziel `backup/`, Retention letzte 5
- Wahrer Auslöser: **Merge `develop` → `staging`** und Push
- Protokoll: **SFTP, Port 22**
- Zielverzeichnis: Default **`schreinerei-frank`** (nach SFTP-Login sichtbar)
