# PHP-Website neuer test

SEO-/KI-optimierte Single-Page-Website für die Schreinerei Frank, Seeon (Chiemgau).
Design: **Konzept C „Meisterhand"** – Details in [`DESIGN.md`](DESIGN.md).

> **Stellschrauben:** Alle zentralen Projektwerte (Firmenname, Domain, E-Mail,
> Farben, Pfade, sichtbare Texte) sind an zentraler Stelle gepflegt und in
> [`PROJECT.md`](PROJECT.md) dokumentiert.

**PHP-Webspace-Setup:** Alle editierbaren Inhalte liegen zentral in
`data/site.json` (Single Source of Truth) und werden **serverseitig per PHP**
in die Seiten gerendert. Der Admin unter `/frank-adm/` schreibt direkt in diese
Datei – Änderungen sind **sofort nach dem Speichern live**, ganz ohne Build.

> Vorteile gegenüber der früheren reinen Client-Rendering-Variante:
> SEO-Tags, Meta, JSON-LD (`LocalBusiness`, `FAQPage`, …), Sitemap, `robots.txt`
> und `llms.txt` werden als **fertiges HTML serverseitig** ausgeliefert – voll
> crawler-/KI-lesbar auch ohne JavaScript.

---

## Voraussetzungen

- **PHP ≥ 8.1** mit JSON, Session und GD (für WebP bei Bild-Upload) – auf einem
  klassischen PHP-Webspace (Apache mit `.htaccess`) bereits vorhanden.
- Optional Node/npm **nur für den lokalen Dev-Server** (der ruft dann PHP auf).

---

## Schnellstart (lokal)

```bash
npm run serve        # nutzt PHP: http://localhost:9999 · Admin: /frank-adm/
```

Ohne npm:

```bash
bash dev/serve.sh    # startet php -S + Router, öffnet Site+Admin in Chrome
```

Beide Befehle werden **aus dem Projekt-Root** (Verzeichnis dieser README)
ausgeführt – `package.json` (Root) bzw. `dev/serve.sh` setzen den Projekt-Root
selbst.

---

## Struktur

```
schreinerei-frank/
├── dev/                        # nur LOKALE Dev-Tools (werden NICHT deployed)
│   ├── serve.sh                # startet `php -S` + Router
│   ├── router.php              # bildet die .htaccess-Regeln lokal nach
│   └── users.php               # lokales CLI-Tool für Admin-Zugänge (unversioniert)
├── package.json                # generiert aus site.json-config; „npm run serve"
├── index.php                   # Router (Home) – bindet Partial-Templates ein
├── impressum/index.php         # nutzt gemeinsame Partials aus /php/
├── datenschutz/index.php       # nutzt gemeinsame Partials aus /php/
├── php/                        # gemeinsame serverseitige Templates + Helfer
│   ├── bootstrap.php           # lädt Daten + Helfer einmalig
│   ├── config.php              # ZENTRALE Projekt-Konfiguration (Namen/Pfade)
│   ├── data.php                # lädt data/site.json, Getter/Helfer
│   ├── seo.php                 # erzeugt <head>-Meta + JSON-LD serverseitig
│   ├── header.php              # <head> + Header (Nav, Tel-Button)
│   ├── footer.php              # Footer + Sticky-Bar + main.js
│   ├── home.php                # Startseiten-Sektionen (Hero, Leistungen, …)
│   └── legal.php               # Impressum / Datenschutz
├── frank-adm/                  # PHP-Admin (Inhalte + Bilder)
│   ├── index.php               # Editor-UI (Login + Formular)
│   ├── api.php                 # JSON-API: login/logout/load/save/upload
│   ├── includes/helpers.php    # Session, Auth, save/load site.json
│   ├── includes/upload.php     # Bild-Upload + WebP-Erzeugung
│   └── assets/                 # admin.css / admin.js
├── data/
│   ├── site.json               # ALLE Inhalte – die Quelle (Single Source)
│   └── admin.json              # Admin-Benutzer-Hashes (dev-Zugänge)
├── js/main.js                  # Interaktion (Slider, FAQ, Formular, …)
├── assets/css/styles.css
├── assets/img/*
├── assets/content/*            # Bilder (inkl. WebP-Varianten)
├── sitemap.php  robots.php  llms.php  webmanifest.php   # dynamisch aus site.json
├── .htaccess                   # saubere URLs + Caching + Zugriffsschutz
└── DESIGN.md  README.md  PROJECT.md  WORKFLOW.md
```

---

## Inhalte bearbeiten (Admin: `/frank-adm/`)

Der Admin ist eine **PHP-App**, die direkt auf `data/site.json` arbeitet:

- **Login** mit Benutzer + Passwort (SHA-256-Hashes).
- **Alle Inhalte** sind über den Schema-Editor editierbar: Texte, Kicker,
  Überschriften, Button-Texte, Leistungen, Trust, FAQ, Rezensionen, Nav,
  Kontaktdaten, Footer und SEO/Meta (Title, Description, OG, LCP-Bild).
- **Speichern** schreibt atomar in `data/site.json` → öffentliche Seite ist
  **sofort aktuell**.
- **Slider (Hero)** und **Galerie/Projekte** werden über die Listen des Editors
  verwaltet; Bilder lassen sich per **Hochladen** (→ `assets/img/`, inkl. WebP)
  hinzufügen oder aus der **Bild-Bibliothek** wählen.
- **Hinweis:** Lade niemals `frank-adm/` auf einen öffentlichen Server, wenn du
  echte Passwort-Hashes in `data/admin.json` abgelegt hast → siehe Sicherheit.

### Zugangsdaten

Admins stehen als SHA-256-Hash-Map in `data/admin.json` (versioniert, wird
gedeployed). Mit dem lokalen Tool `dev/users.php` lassen sie sich anlegen bzw.
ändern (siehe `WORKFLOW.md` §9). Aktuelle Entwicklungs-Zugänge:

| Benutzer | Passwort |
|---|---|
| `lmair` | siehe `dev/users.php` bzw. `.env` (lokal hinterlegt) |
| `stefan` | `demo123` (nur Test/Entwicklung) |

**Echte Passwörter vor dem Live-Gang ändern** – Hash erzeugen:

```bash
echo -n "neuespasswort" | shasum -a 256
```

### Sicherheit (wichtig)

- Für **Produktion** die echten Passwort-Hashes **nicht in Git** legen, sondern
  in einer **nicht versionierten** Datei `data/admin.local.json` (dieses Schema
  wird automatisch bevorzugt gelesen und ist per `.gitignore`/`.htaccess`
  geschützt). Beispiel-Inhalt:

  ```json
  { "admin": "<sha256-hash>" }
  ```

- `.htaccess` blockiert den direkten Zugriff auf `data/*.json`, `.env`,
  `/php/*.php` und `/frank-adm/includes/*.php`.
- In `data/admin.json` können die Hashes leer bleiben bzw. nur der
  Standard-Login stehen, solange die echten Zugangsdaten nur in
  `data/admin.local.json` liegen.

---

## Seitentypen & Routing

- `index.php` ist ein **Router**: `/?page=impressum|datenschutz` oder die
  physischen Ordner `/impressum/`, `/datenschutz/` binden dieselben
  Partial-Templates aus `/php/` ein.
- Alle Inhalte/SEO kommen **serverseitig** aus `site.json` – kein reines
  Client-Rendering mehr; `js/render.js` wurde entfernt.
- `js/main.js` liefert weiterhin die Interaktion (Nav, FAQ-Akkordeon, Slider,
  Lightbox, Formular, Cookie-Banner) per Progressive Enhancement.

### SEO / KI (automatisch aktuell)

- `sitemap.xml`, `robots.txt`, `llms.txt` werden per `.htaccess`/Router aus
  `sitemap.php`, `robots.php`, `llms.php` dynamisch aus `site.json` erzeugt
  (Site-URL, Leistungen, Einzugsgebiet, Kontakt) – immer synchron zum Inhalt.
- `robots.txt` erlaubt ausdrücklich relevante KI-/LLM-Crawler.
- JSON-LD (`LocalBusiness`, `Carpenter`, `Organization`, `WebSite`, `WebPage`,
  `BreadcrumbList`, `FAQPage`, `OfferCatalog`) wird serverseitig eingebettet.

---

## Deployment

Der Live-Gang läuft **vollautomatisch über GitHub Actions** per SFTP zum Hoster
(Backup vor jedem Upload, Retension der letzten 5 Zips). Auslöser ist immer ein
`develop → staging`-Merge. **Alle Details: [WORKFLOW.md](WORKFLOW.md)**.

> **Erster Deploy auf einen frischen Server:**
> Damit die Pipeline überhaupt funktioniert, müssen anfangs **nur** der
> `backup.php` und der Ordner `backup/` (inkl. `.htaccess` gegen öffentlichen
> Zugriff) auf dem Server liegen – alles andere übernimmt der Workflow beim
> ersten Merge. Der `BACKUP_URL` sollte danach sofort lieferbar sein
> (`https://<subdomain>/backup.php`).

Manuell wären folgende Schritte ohnehin nötig (vom Workflow übernommen):

1. Inhalte in `data/site.json` pflegen (oder im Admin `/frank-adm/`).
2. `data/admin.local.json` mit echten Passwort-Hashes für Produktion anlegen
   (siehe oben; nie committen – wird bewusst nicht deployed).
3. Domain in `site.json` (`config.domain` bzw. Meta `siteUrl`) prüfen – Sitemap,
   Robots und llms ziehen sie automatisch.
4. **Search Console** Property + Sitemap; **Google-Unternehmensprofil**
   verknüpfen; Rich-Results-Test: <https://search.google.com/test/rich-results>.

**Nicht deployen:** `.env`, `dev/`, `*.md`-Dokumentation, `package.json`,
`.github/`, `data/admin.local.json` (alles in der Workflow-`exclude`-Liste).

---

## Kontaktformular anbinden

Das Formular (in `php/home.php`) postet normal an `action="/api/kontakt"`.
Für den Versand einen Formulardienst eintragen (Web3Forms, Formspree, …) oder
ein kleines PHP-Skript `api/kontakt.php` anlegen (Honeypot ist vorhanden:
Feld `website`).

---

## Qualitäts-Checkliste (aktueller Stand)

- [x] eine `<h1>`, saubere `h2`-Hierarchie, Landmarks (`header/nav/main/footer`)
- [x] JSON-LD serverseitig eingebettet (LocalBusiness/Carpenter, Organization, WebSite, WebPage, BreadcrumbList, FAQPage, OfferCatalog)
- [x] Meta-Title + Description, Open Graph, Twitter Card, `canonical`, `lang="de"`
- [x] `robots.txt` (+ KI-Bots) / `sitemap.xml` / `llms.txt` dynamisch aus `site.json`
- [x] No-JS-Fallback: Inhalte serverseitig vorhanden (kein Client-Rendering mehr)
- [x] alle Bilder mit `alt`, `width`/`height`, Lazy-Load unter dem Fold
- [x] Admin (PHP) mit Bild-Upload + WebP, schreibt direkt in `site.json`
- [ ] echte Bilder + OG-/Icon-Assets prüfen
- [ ] Fonts self-hosten (aktuell Google Fonts CDN)
- [ ] Formular-Backend anbinden
- [ ] Impressum-/Datenschutz-Texte final prüfen
