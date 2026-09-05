# TEMPLATE.md – Schreinerei-Frank als wiederverwendbare Astro-Template-Site

Dieses Repository ist eine **statische Beispiel-Site auf Astro-Basis** mit einem
PHP-Web-Admin (Hybrid). Alle Inhalte kommen aus `data/site.json` – kein einziger
kundenbezogener Text ist hart im Astro-Code. Für ein neues Projekt reicht der
`config`-Block in `data/site.json` + Austausch der Assets.

---

## Voraussetzungen

- Node.js ≥ 22.12 (siehe `.nvmrc`), `nvm` optional
- Für Publish/Deploy: GitHub-Repo + Host mit SFTP-Zugang

## Lokale Entwicklung

```bash
nvm use                       # Node 22 laden
npm install                   # Dependencies (einmalig)
npm run dev                   # Astro-Dev-Server mit Hot-Reload
npm run preview               # gebaute Site lokal ansehen (nach build)
npm run build                 # statische Site nach dist/
```

Die Seite wird **aus der Repo-`data/site.json`** gebaut – identische Sektionen
wie auf dem Server, ohne diesen zu brauchen.

## Rebranding für ein neues Projekt

1. **`data/site.json` → `config`-Block anpassen** (Name, Domains, Telefon/Fax,
   `themeColor`, `logo`, E-Mail, Öffnungszeiten, Einzugsgebiet …). Alle
   `{config.X}`-Platzhalter werden beim Bauen durch diese Werte ersetzt.
2. **`public/assets/` austauschen** (Logo `site/logo.png`, `site/og.jpg`,
   `content/`-Bilder); Design-Farben im CSS `src/styles/global.css`
   (`--primary`, `--accent`, … `:root`).
3. **SEO/Legal-Texte** (`meta`, `legal`, alle Textsektionen) anpassen.
4. `nvm use && npm run build`, dann normaler Git-/CI-Flow.

## Architektur-Überblick

```
feature/astro
├─ astro.config.mjs        # build.outDir = dist/
├─ src/
│  ├─ layouts/Base.astro   # <html>, Head/Seo, Header, Footer, Cookie
│  ├─ components/
│  │  ├─ seo/Seo.astro     # Titel/OG/Twitter/JSON-LD (Google-Fonts-Link)
│  │  └─ sections/         # Hero, Trust, Services, Gallery, About, Faq,
│  │                       # Testimonials, Contact (austauschbar)
│  ├─ pages/               # index, impressum, datenschutz
│  │  ├─ sitemap.xml.ts / robots.txt.ts / llms.txt.ts / site.webmanifest.ts
│  ├─ styles/global.css    # Design-Tokens + alle Regeln (via Base importiert)
│  └─ lib/
│     ├─ data.ts           # site.json laden + {config.X}-Resolve
│     └─ seo.ts            # JSON-LD @graph
├─ public/assets/          # Bilder, Content (statisch deployen; kein CSS mehr)
├─ frank-adm/              # PHP-Admin (bleibt; + publish/sitejson-Endpoints)
└─ data/site.json          # Content-Wahrheit
```

## Verbleibende PHP-Reste

Nur diese Endpunkte laufen weiter mit PHP auf dem Hoster (alles andere ist
statisch aus `dist/`):

| Pfad                | Zweck                                   |
|---------------------|-----------------------------------------|
| `frank-adm/`        | Web-Admin (Login, Content-Editor, Upload) |
| `api/kontakt.php`   | Kontakt-Formular (`mail()`)             |
| `backup.php`        | ZIP-Sicherung vor Deploy                |

`.htaccess` liefert die statische Site aus und schützt die PHP-Ordner/die
`data/`-Dateien. Der Admin-Bereich läuft unter `/frank-adm/` (konfigurierbar).

## Publish-Flow („Veröffentlichen" per Klick)

Ablauf: Admin-Speichern → `frank-adm/api.php?action=publish` → GitHub
`repository_dispatch` → Workflow `.github/workflows/publish.yml` holt die
**serveraktuelle** `data/site.json`, baut mit Astro und deployed per SFTP.

### Secrets – GitHub (`Settings → Secrets and variables → Actions`)

| Secret            | Zweck                                             |
|-------------------|---------------------------------------------------|
| `FTP_SERVER`      | SFTP-Host                                         |
| `FTP_USERNAME`    | SFTP-Benutzername                                 |
| `FTP_PASSWORD`    | SFTP-Passwort                                     |
| `FTP_TARGET_DIR`  | absoluter Web-Root-Pfad (aus `backup.php`-Doku)   |
| `BACKUP_URL`      | Backup-Endpoint-URL                               |
| `BACKUP_TOKEN`    | Backup-Token                                      |
| `SITE_BASE`       | Basis-URL des Hosts, z. B. `https://schreinerei-frank.typopublic.com` |
| `SITEJSON_TOKEN`  | Token für `?action=sitejson&token=…` (siehe unten)|

### GitHub-PAT anlegen (für `PUBLISH_TOKEN`)

Der `PUBLISH_TOKEN` ist ein **Personal Access Token** mit Repo-Schreibrecht auf
das Publish-Repo.

> **Empfehlung:** Für den `repository_dispatch`-Klick ist ein **Classic-Token
> mit `repo`-Scope** der zuverlässige Weg. Fine-grained-Token (auch mit
> Contents/„Actions: Read and write“) bekommen bei
> `POST /repos/…/dispatches` in der Praxis **weiterhin HTTP 403 „Resource not
> accessible“** (siehe Troubleshooting). Classisches `repo` deckt alle
> nötigen Rechte ab.

#### Weg B – Classic-Token (empfohlen, zuverlässig)

1. Settings → **Developer settings** → **Personal access tokens** →
   **Tokens (classic)**.
2. **„Generate new token (classic)"**.
3. *Expiration* setzen; Häkchen bei **`repo`** (komplette Repo-Berechtigung,
   inkl. Contents- und Actions-Write → deckt den `repository_dispatch` ab).
4. **„Generate token"** → kopieren (Prefix `ghp_`).

#### Weg A – Fine-grained-Token (optional, repo-genau)

1. GitHub → Avatar → **Settings** → ganz unten in der Seitenleiste
   **Developer settings** → **Personal access tokens** → **Fine-grained tokens**
   (direkt: `github.com/settings/personal-access-tokens`).
2. **„Generate new token"**.
3. Felder:
   - *Token name*: z. B. `schreinerei-publish`
   - *Expiration*: z. B. 90 Tage
   - *Repository access*: **„Only select repositories"** → `schreinerei-frank`
   - *Permissions → Repository permissions*: **Contents → Read and write**
     („Read-only" erscheint nur, wenn die Repo-Auswahl auf „All repositories"
     oder „Public repositories" steht – dann Auswahl korrigieren).
4. **„Generate token"** klicken → der Token erscheint **einmalig** in einem
   blauen Kasten → **„Copy"** → vollständig kopieren (Prefix `github_pat_`).

**Update geben:** Werte in der Liste sind maskiert; den vollen Wert gibt es nur
direkt nach dem Generieren (sonst **„Regenerate"** → neuen Wert nehmen).

**Troubleshooting Token:**
- „Bad credentials“ (HTTP 401 beim Dispatch) = Token unvollständig/abgeschnitten
  oder nicht mehr gültig. Vollständig kopieren (Fine-grained ≈ 90+ Zeichen,
  wird nur direkt nach dem Generieren vollständig angezeigt).
- Check zur schnellen Prüfung (Wert nicht ins Repo, nur lokal):
  `curl -H "Authorization: Bearer <token>" https://api.github.com/user`
  → HTTP 200 + `login` = gültig, HTTP 401 = ungültig.

### `.env` auf dem Server (NIE versionieren)

| Key              | Zweck                                                        |
|------------------|--------------------------------------------------------------|
| `ADMIN_USERS`    | Admin-Passwort-Hashes (bestehend)                            |
| `SITEJSON_TOKEN` | Token, mit dem der Workflow die site.json abrufen darf       |
| `PUBLISH_TOKEN`  | GitHub PAT (Repo-Schreibrecht) zum Triggern des Workflows    |

Der `sitejson`-/`publish`-Endpoint ist nur mit einem dieser Tokens erreichbar
(Konstant-Zeit-Vergleich). Ohne konfigurierten `PUBLISH_TOKEN` liefert
`action=publish` einen 500-Fehler statt eines Dispatch-Versuchs.

### Workflow-Eckpunkte (`publish.yml`)

1. Checkout + Node 22,
2. lädt `data/site.json` vom Server via `action=sitejson` (Validierung auf
   `config`/`business`),
3. `npm ci && npm run build`,
4. stellt `_deploy/` zusammen: `dist/` + `frank-adm/` + `api/kontakt.php` +
   `backup.php` + `.htaccess` (kein `data/` im Web-Root),
5. Backup-Aufruf vor Deploy,
6. inkrementaler SFTP-Upload (`_deploy` → `FTP_TARGET_DIR`).

Der alte `deploy-staging.yml` (Code/Branch-Flow) bleibt **ergänzend bestehen**;
der Publish-Workflow ist der neue Content-Flow. `github/workflows/publish.yml`
wird erst nach dem Merge von `feature/astro` auf den Standard-Branch aktiviert
(repository_dispatch greift nur auf den Standard-Branch).

### Lokal testen (alles ohne Server/GitHub)

Der Publish-Flow besteht aus drei Etappen – die ersten ZWEI sind lokal
vollständig abbildbar, die dritte erst mit Server+GitHub-Secrets:

| Etappe | Lokal testbar? |
|---|---|
| A) Admin bearbeiten + speichern | ✅ ja (`php -S` + Admin) |
| B) site.json abholen + build + `_deploy/` bauen | ✅ ja (identische Schritte des Workflows) |
| C) Backup + SFTP (+ GitHub-Dispatch) | ❌ erst mit Secrets/Server |

#### A) Admin lokal starten (Bearbeiten + Speichern)

```bash
php -S 127.0.0.1:8000 -t .       # Admin: http://127.0.0.1:8000/frank-adm/
```

Einloggen (Entwicklungs-Zugang aus `data/admin.json` bzw. `dev/users.php`),
inhalte ändern → **Speichern** schreibt `data/site.json`. Im Astro-Modus hat
das noch keine öffentliche Wirkung – die Seite wird ja beim Build erzeugt.

#### B) Publish-Simulation (die eigentlichen Workflow-Schritte)

```bash
# 1) „Aktuelle“ site.json abholen – genauso wie publish.yml:
curl -fsSL "http://127.0.0.1:8000/frank-adm/api.php?action=sitejson&token=<TOKEN>" -o data/site.json
#    (<TOKEN> = SITEJSON_TOKEN aus .env; ohne .env-Eintrag antwortet 401)

# 2) Build – identisch zum Workflow:
npm run build

# 3) Deploy-Inhalt zusammenstellen (_deploy) – identisch zum Workflow:
rm -rf _deploy && mkdir _deploy
cp -r dist/. _deploy/
cp -r frank-adm _deploy/frank-adm
mkdir -p _deploy/api && cp api/kontakt.php _deploy/api/
cp backup.php _deploy/ && cp .htaccess _deploy/ && rm -rf _deploy/data

# 4) Ergebnis ansehen (simuliert den Web-Root):
npm run preview -- --port 4322
```

Damit verifiziertst du den kompletten Build-Pfad mit einer serverseitig
gespeicherten site.json, inkl. SEO-Dateien und `_deploy`-Zusammensetzung —
der einzige Unterschied zum echten Publish sind Backup-Call und SFTP-Upload.

#### C) Was erst mit Zugangsdaten geht

- **`action=publish`** (GitHub-`repository_dispatch`): lokal liefert er
  sinnvollerweise 500 („PUBLISH_TOKEN nicht konfiguriert“) – das ist der
  Beweis, dass der Endpoint erreicht wird.
- **Senderichtung „Soft-live“**: der Admin-Button ruft den Endpoint korrekt auf;
  erst mit echten Secrets triggert er den GitHub-Workflow.
- Backup-Aufruf + SFTP-Deploy laufen ausschließlich auf dem Runner.

#### Test der Endpoints

- `frank-adm/api.php?action=sitejson&token=…` → liefert die site.json (401 bei
  fehlendem/falschem Token).
- `POST admin …action=publish` → 500 ohne PAT (wird bei echdem Setup 200).
- `workflow_dispatch` in der GitHub-UI startet den Publish ohne Server-Dispatch
  (praktisch für den Test auf `schreinerei-frank-astro.typopublic.com`).

---

## Bekannte Trade-offs

- Content ist nach „Veröffentlichen" in ~1–2 min live (statt sofort).
- Galerie-Bilder werden statisch geliefert (kein serverseitiges WebP/AVIF beim
  Request); neue Uploads erzeugt der Admin wie bisher.
- `mail()` fürs Kontaktformular läuft weiter über den Host, nur `/api/kontakt.php`.