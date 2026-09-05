# STRUCTURE.md – Ordner- und Dateistruktur

Zweck: schnelle Orientierung im Repository. Wer was wofür ändern will, findet
hier die zuständige Datei. Vertiefungen: `README.md` (Überblick & Setup),
`TEMPLATE.md` (Template-/Publish-Flow), `ASTRO.md` (Migrationsstand &
offene Punkte), `DESIGN.md` (Design-System).

> **Stand:** `feature/astro` (Astro-Template, Hybrid: statische Astro-Site +
> PHP-Admin).

---

## Übersicht

```
Schreinerei Frank (Web-Root)
├── src/                  # Statische Astro-Site (Code, wird zu dist/ gebaut)
├── public/               # Statische Assets (1:1 nach dist/ kopiert)
├── data/                 # Inhalte + Admin-Zugänge (site.json = Single Source)
├── frank-adm/            # PHP-Web-Admin (bleibt bestehen)
├── php/                  # PHP-Lese-Helfer (nur noch für Admin/Kontakt)
├── api/                  # PHP-Endpunkte (Kontaktformular)
├── backup.php, backup/   # ZIP-Sicherung vor Deploy
├── dev/                  # Lokale Dev-Werkzeuge (PHP-Server, User-CLI)
├── .github/workflows/    # GitHub Actions (Publish + Code-Deploy)
├── *.json / *.mjs / ts  # Konfiguration (Astro, Node, TypeScript)
├── .htaccess            # Web-Server-Regeln (statische Seiten + PHP-Reste)
└── *.md                  # Doku (README, TEMPLATE, ASTRO, DESIGN, STRUCTURE …)
```

Nach `npm run build` entsteht `dist/` (die fertige, deploybare Site). Für den
Publish-Workflow wird daraus zusätzlich `_deploy/` zusammengestellt
(dist + Admin + PHP-Reste + `.htaccess`, ohne `data/`).

---

## Konfiguration (Wurzel)

| Datei | Aufgabe |
|---|---|
| `astro.config.mjs` | Astro-Konfiguration: `site` (Test-Subdomain), `output: 'static'`, `build.assets = 'assets'` → Assets-Ausgabeordner in `dist/`. |
| `package.json` | Node-Projekt mit Astro 6; Scripts `dev`, `build`, `preview`. |
| `package-lock.json` | Node-Dependency-Sperrliste (für reproduzierbare CI-Builds). |
| `tsconfig.json` | TypeScript-/Astro-TS-Einstellungen (für `*.ts`-Seiten + lib). |
| `.nvmrc` | Node-Version (22) für `nvm use`. |
| `.gitignore` | Ignoriert u. a. `node_modules/`, `dist/`, `_deploy/`, `.env`, `data/admin.local.json`, `data/kontakt.local.json`, `dev/users.php`, `dev/server.log`. |
| `.htaccess` | Apache-Regeln für den Live-Web-Root: Verzeichnis-URLs → `index.html` (statische Seiten), Admin-Konfiguration (`/frank-adm`), Schutz von `data/`, `.env`, PHP-Resten, Caching, Security-Header. |
| `.env` | **Nicht versioniert.** Lokale/Server-Secrets (`ADMIN_USERS`, `SITEJSON_TOKEN`, später `PUBLISH_TOKEN`). Gelesen von `frank-adm/includes/helpers.php`. |
| `.env.local` | **Nicht versioniert, vom Code ungelesen.** Persönliche Notizzwecke. |

---

## `src/` – statische Astro-Site

| Pfad | Aufgabe |
|---|---|
| `layouts/Base.astro` | Basis-Layout aller Seiten: `<html lang="de" class="no-js">`, `no-js→js`-Swap, SEO-Head, Cookie-Banner, Header, `<main>`, Footer, lädt `global.css` + `main.js`. |
| `components/CookieBanner.astro` | Cookie-Hinweis (nur notwendige Cookies; Einwilligung in `localStorage`). |
| `components/SiteHeader.astro` | Sticky-Header mit Burgen-Navigation, Tel-Button, Skip-Link. |
| `components/SiteFooter.astro` | Footer (Firmendaten, Öffnungszeiten, Impressum/Datenschutz-Links). |
| `components/seo/Seo.astro` | Head-SEO: Title/Description, Open Graph, Twitter, Canonical, `theme-color`, Icon-Links, Google-Fonts-CDN, JSON-LD-Struktur (`seo.ts`). |
| `components/sections/Hero.astro` | Titel-Sektion mit Slider (`hero.images`), Claim, CTAs, LCP-Bild. |
| `components/sections/Trust.astro` | Vertrauens-Row (Meisterbetrieb, Werkstatt, Hölzer, Oberflächen). |
| `components/sections/Services.astro` | Leistungen-Karten mit SVG-Icons. |
| `components/sections/Gallery.astro` | Projekt-Galerie (Slider + Lightbox mit Thumbs, Autoplay, Zustands-Ansage). |
| `components/sections/About.astro` | „Über uns“ mit Vertrauens-Punkten und Personenfoto. |
| `components/sections/Faq.astro` | FAQ-Akkordeon (native `<details>` + JS-Single-Open). |
| `components/sections/Testimonials.astro` | Kundenrezensionen-Slider. |
| `components/sections/Contact.astro` | Kontaktformular + Adressbox (Versand an `api/kontakt.php`). |
| `pages/index.astro` | Startseite (reiht die Sektionen). |
| `pages/impressum.astro` | Impressum-Seite (Content aus `site.json → legal.impressum`). |
| `pages/datenschutz.astro` | Datenschutz-Seite (`site.json → legal.datenschutz`). |
| `pages/sitemap.xml.ts` | Build-Output `sitemap.xml` aus `site.json`. |
| `pages/robots.txt.ts` | Build-Output `robots.txt` (inkl. erlaubter KI-/LLM-Crawler). |
| `pages/llms.txt.ts` | Build-Output `llms.txt` (Leistungen, Einzugsgebiet, Kontakt). |
| `pages/site.webmanifest.ts` | Build-Output Web-App-Manifest (Name, `theme_color`, Icons). |
| `lib/data.ts` | Lädt `data/site.json`, löst `{config.X}`-Platzhalter, stellt Helfer bereit („Single Source of Truth“). |
| `lib/seo.ts` | Erzeugt das JSON-LD-`@graph` (LocalBusiness/Carpenter, Organization, FAQPage …). |
| `scripts/main.js` | Client-Interaktion: Nav-Toggle, Slider, Gallery/Lightbox, FAQ-Akkordeon, Formular-Fetch, Cookie-Banner (Progressive Enhancement). |
| `styles/global.css` | **Einziges CSS**: Design-Tokens (`:root`), Basis, Layout, Komponenten, Utilities. Via `Base.astro` importiert → Astro/Vite-Bundle (minifiziert, gehast). |

---

## `public/` – statische Assets (1:1 nach `dist/`)

| Pfad | Aufgabe |
|---|---|
| `favicon.ico` | Browser-Favicon (referenziert in `Seo.astro` + `site.webmanifest`). |
| `assets/site/` | Logos und Icons: `logo.png`, `frank-logo-opt.png`, `og.jpg` (Social-Share), `icon-192.png`, `icon-512.png`, `apple-touch-icon.png`. |
| `assets/content/` | Bildinhalte je Kategorie (`badmoebel/`, `fenster/`, `hero/`, `innenausbau/`, `kontakt/`, `treppen/`, `tueren/`). Pfade kommen aus `data/site.json`. |
| `assets/img/` | Bild-Zielordner für Admin-Uploads; enthält aktuell das LCP-Bild `projekt-badmoebel-5.jpg` (Referenz: `site.json → meta.lcpImage`). |

---

## `data/` – Inhalte & Zugänge

| Datei | Aufgabe |
|---|---|
| `data/site.json` | **Content-Wahrheit:** Config-Block, Kontakt, Business, Nav, Meta/SEO, alle Sektionstexte, Galerie, FAQ, Services, Rezensionen, UI-Beschriftungen, Legal-Texte. Basis für Build **und** Admin. |
| `data/admin.json` | Versionierte Admin-Zugänge (SHA-256-Hash-Map, Entwicklungs-Logins). |
| `data/admin.local.json` | **Nicht versioniert** (optional): echte Produktions-Passwörter, wird von `adm_users_file()` bevorzugt gelesen. |
| `data/kontakt.local.json` | **Nicht versioniert** (optional): Empfänger-Umbiegung für lokale Formular-Tests. |

---

## `frank-adm/` – PHP-Web-Admin

| Pfad | Aufgabe |
|---|---|
| `index.php` | Admin-UI (Login, Schema-Editor, Listen/Galerie, Upload, „Veröffentlichen“-Button). |
| `api.php` | Admin-API: `login`, `save`, `images`, `sitejson` (GET, token-geschützt), `publish` (POST, token-/Session-geschützt → GitHub `repository_dispatch`). |
| `includes/helpers.php` | Sitzung, Auth, `adm_env()` (liest `.env`), Content lesen/schreiben, Token-Helfer (`SITEJSON_TOKEN`, `PUBLISH_TOKEN`). |
| `includes/upload.php` | Bild-Upload (→ `assets/img/`, WebP-Variante), Bild-Bibliothek. |
| `assets/admin.css` / `assets/admin.js` | Styling + Interaktion der Admin-UI (inkl. `publish()`-Aufruf). |

---

## `php/` – verbleibende PHP-Helfer (Legacy, nur noch für Admin/Kontakt)

| Datei | Aufgabe |
|---|---|
| `php/config.php` | Liest den `config`-Block aus `data/site.json` (Name, Domain, ThemeColor, `adminPath`, `port` …) und liefert das Projekt-Konfig-Array. |
| `php/data.php` | `site_load()`: lädt `site.json`, resolvert `{config.X}`, stellt PHP-Helfer bereit (wird von Admin/Kontakt genutzt). |

> Die frühere Funktion „.htaccess/package.json automatisch generieren“
> (`config_build_files()`/`config_sync()`) ist abgeschaltet → beide Dateien sind
> jetzt fest im Repo (siehe `config.php`-Kommentare).

---

## Endpunkte & Backup (PHP auf dem Hoster)

| Datei | Aufgabe |
|---|---|
| `api/kontakt.php` | Kontakt-Formular-Endpoint: serverseitige Validierung, Honeypot, `mail()` (oder SMTP via `contact.mailer`); JSON- oder HTML-Antwort je nach Request. |
| `backup.php` | Legt vor dem Deploy einen datierten ZIP-Backup an (Retention: letzte 5, `BACKUP_KEEP`), gesichert durch `BACKUP_TOKEN`. |
| `backup/.htaccess` | Schützt `backup/` gegen öffentlichen Zugriff. |

---

## `dev/` – lokale Entwicklungs-Werkzeuge (nicht deployen)

| Datei | Aufgabe |
|---|---|
| `dev/serve.sh` | Startet den lokalen PHP-Dev-Server (Port ab `config.port`, Router via `router.php`), öffnet Site + Admin in Chrome, loggt nach `dev/server.log`. |
| `dev/router.php` | PHP-Router des lokalen Servers (leitet `/api/…`, `/frank-adm/…` etc.). |
| `dev/users.php` | **Nicht versioniert.** CLI zur Admin-Verwaltung: `php dev/users.php add/list/setpw/remove/hash …` (schreibt `data/admin.json`, spiegelt in `.env`). |
| `dev/server.log` | **Nicht versioniert.** Lauf-Log des lokalen PHP-Servers. |

---

## CI/CD (`.github/workflows/`)

| Datei | Aufgabe |
|---|---|
| `publish.yml` | **Content-Publish:** getriggert per `repository_dispatch` (Admin „Veröffentlichen“) oder `workflow_dispatch`. Holt serveraktuelle `site.json` (`action=sitejson` + `SITEJSON_TOKEN`), validiert auf `config`/`business`, `npm ci && npm run build`, stellt `_deploy/` zusammen (dist + frank-adm + api + backup.php + .htaccess, ohne `data/`), Backup-Call, inkrementeller SFTP-Upload. |
| `deploy-staging.yml` | **Code-Deploy** (bestehend, ergänzend): lädt bei `develop → staging`-Merge den Stand per SFTP hoch. |

---

## Doku-Markdown-Dateien

| Datei | Inhalt |
|---|---|
| `README.md` | Überblick, Voraussetzungen, Schnellstart, Struktur, Admin-Bedienung, Deployment/Publish, Kontaktformular, Qualitäts-Checkliste. |
| `TEMPLATE.md` | „Rezept“ für ein Zweitprojekt: Rebranding, Architektur, PHP-Reste, Publish-Flow + Secrets, lokaler Test (Etappen A–C). |
| `ASTRO.md` | Migrationsstand: was gebaut ist, was noch fehlt (Secrets, Subdomain-Test, Abnahme/Switch), CSS-/Build-Doku. |
| `DESIGN.md` | Design-System „Meisterhand“: Farb-Tokens, Typografie, Raster, Komponenten, Galerie-Spezifikation, Assets. |
| `STRUCTURE.md` | Diese Datei – Ordner-/Dateistruktur. |
| `PROJECT.md` / `WORKFLOW.md` | Ältere Projekt-/Deploy-Doku (teils überholt durch TEMPLATE/ASTRO; enthält noch historische PHP-Details). |

---

## Generiert / ignoriert (nicht versionieren)

| Pfad | Herkunft |
|---|---|
| `node_modules/` | `npm install`. |
| `dist/` | `npm run build` – die deploybare statische Site. |
| `_deploy/` | Publish-Workflow: dist + PHP-Reste als „fertiger Web-Root“. |
| `backup/` | Nur `backup/.htaccess` ist versioniert; ZIPs entstehen auf dem Server. |
| Einheiten wie `data/admin.local.json`, `.env`, `.env.local`, `dev/users.php`, `dev/server.log` | Lokale/geheime Werte, bewusst ignoriert. |