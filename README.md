# Schreinerei Frank – Astro-Template-Site

SEO-/KI-optimierte Single-Page-Website für die Schreinerei Frank, Seeon (Chiemgau).
Design: **Konzept C „Meisterhand"** – Details in [`DESIGN.md`](DESIGN.md).

> **Stellschrauben:** Alle zentralen Projektwerte (Firmenname, Domain, E-Mail,
> Farben, Pfade, sichtbare Texte) sind an zentraler Stelle gepflegt und in
> [`PROJECT.md`](PROJECT.md) dokumentiert.

**Hybrid-Architektur:** Alle editierbaren Inhalte liegen zentral in
`data/site.json` (Single Source of Truth). Die öffentliche Seite ist eine
**statische Astro-Site** (Build-Time-HTML in `dist/`), gepflegt weiterhin über
den **PHP-Admin** `/frank-adm/`. Änderungen im Admin werden beim
**„Veröffentlichen"** im Admin per GitHub-Action neu gebaut und per SFTP live
gespielt (~1–2 min). Der Doku- und Rebranding-Überblick steht in
[`TEMPLATE.md`](TEMPLATE.md), der Migrationsstand in
[`ASTRO-TEMPLATE-PLAN.md`](ASTRO-TEMPLATE-PLAN.md).

> **Warum statisch:** maximale Geschwindigkeit ohne PHP zur Laufzeit; SEO-Tags,
> Meta, JSON-LD (`LocalBusiness`, `FAQPage`, …), Sitemap, `robots.txt` und
> `llms.txt` werden beim Build als **fertiges HTML** erzeugt – voll crawler-/
> KI-lesbar ohne JavaScript.

---

## Voraussetzungen

- **Node.js ≥ 22.12** siehe `.nvmrc` (für lokale Entwicklung + Build).
- **PHP ≥ 8.1** (nur für die verbleibenden Endpunkte auf dem Hoster:
  Admin `frank-adm/`, Kontakt `api/kontakt.php`, `backup.php`).

---

## Schnellstart (lokal)

```bash
nvm use            # Node 22 laden (siehe .nvmrc)
npm install        # einmalig
npm run dev        # Astro-Dev-Server: http://localhost:4321 (Hot-Reload)
npm run build      # statische Site nach dist/
npm run preview    # gebaute Site lokal ansehen
```

Die Seite wird **aus der Repo-`data/site.json`** gebaut – identische Sektionen
wie auf dem Server, ganz ohne PHP.

---

## Struktur

```
schreinerei-frank/
├── astro.config.mjs             # Astro: site, output static, outDir dist/
├── package.json                 # Astro-Scripts (dev/build/preview), Node 22
├── src/                         # Statische Site (Astro)
│   ├── layouts/Base.astro       # <html>, Seo, Header, Footer, Cookie-Banner
│   ├── components/
│   │   ├── seo/Seo.astro        # Titel/OG/Twitter/JSON-LD (Port seo.php)
│   │   └── sections/            # Hero, Trust, Services, Gallery, About,
│   │                            #   Faq, Testimonials, Contact (austauschbar)
│   ├── pages/                   # index, impressum, datenschutz
│   │   ├── sitemap.xml.ts       # Build-Output: SEO-/KI-Dateien
│   │   ├── robots.txt.ts        #   (statt PHP-Endpunkte)
│   │   ├── llms.txt.ts
│   │   └── site.webmanifest.ts
│   ├── scripts/main.js          # Interaktion (Slider, FAQ, Formular, …)
│   └── lib/                     # data.ts (site.json), seo.ts (JSON-LD)
├── public/                      # statisch 1:1 nach dist/
│   ├── assets/                  # css, img, content, site (Bilder/CSS)
│   └── favicon.ico
├── dist/                        # Build-Output (generiert, gitignored)
├── frank-adm/                   # PHP-Admin bleibt: index, api, includes
├── api/kontakt.php              # Kontakt-Formular (PHP `mail()`)
├── backup.php                   # ZIP-Sicherung vor Deploy
├── php/                         # Legacy-PHP-Helfer (data.php/config.php/seo.php)
│                                #   bleiben als Referenz für Admin + Migrations-Port
├── data/site.json               # ALLE Inhalte – die Quelle (Single Source)
├── .github/workflows/
│   ├── publish.yml              # NEU: Content-Publish (Repository-Dispatch → Build+Deploy)
│   └── deploy-staging.yml       # bestehend: Code-Deploy bei develop→staging-Merge
├── .htaccess                    # statische Site + PHP-Reste (Admin/Kontakt/Backup)
└── DESIGN.md  README.md  PROJECT.md  WORKFLOW.md  TEMPLATE.md  ASTRO-TEMPLATE-PLAN.md
```

---

## Inhalte bearbeiten (Admin: `/frank-adm/`)

Der Admin ist eine **PHP-App**, die direkt auf `data/site.json` arbeitet:

- **Login** mit Benutzer + Passwort (SHA-256-Hashes).
- **Alle Inhalte** sind über den Schema-Editor editierbar: Texte, Kicker,
  Überschriften, Button-Texte, Leistungen, Trust, FAQ, Rezensionen, Nav,
  Kontaktdaten, Footer und SEO/Meta (Title, Description, OG, LCP-Bild).
- **Speichern** schreibt atomar in `data/site.json` (Server-Wahrheit) → danach
  **„Veröffentlichen"** baut und deployed die statische Site (~1–2 min).
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

- Statische Seiten aus `src/pages/`: `index.html`, `impressum/index.html`,
  `datenschutz/index.html`. `.htaccess` leitet Verzeichnis-URLs auf die
  jeweilige `index.html`.
- Alle Inhalte/SEO kommen **beim Build** aus `site.json`; `src/scripts/main.js`
  liefert die Interaktion (Nav, FAQ-Akkordeon, Slider, Lightbox, Formular,
  Cookie-Banner) per Progressive Enhancement und wird von Astro gebundelt.

### SEO / KI (beim Build automatisch aktuell)

- `sitemap.xml`, `robots.txt`, `llms.txt`, `site.webmanifest` werden in
  `src/pages/*.ts` beim Build aus `site.json` erzeugt (Site-URL, Leistungen,
  Einzugsgebiet, Kontakt) – immer synchron zum Inhalt.
- `robots.txt` erlaubt ausdrücklich relevante KI-/LLM-Crawler.
- JSON-LD (`LocalBusiness`, `Carpenter`, `Organization`, `WebSite`, `WebPage`,
  `BreadcrumbList`, `FAQPage`, `OfferCatalog`) wird im HTML eingebettet.

---

## Deployment & Publish

**Code-Deploy** (Astro-/Admin-Änderungen) läuft wie bisher über den
`develop → staging`-Merge → `deploy-staging.yml` per SFTP zum Hoster
(Backup vor jedem Upload). **Alle Details: [WORKFLOW.md](WORKFLOW.md)**.

**Content-Publish** (Admin-Inhalte → live) läuft neu über den
`publish.yml`-Workflow:

1. Im Web-Admin „Veröffentlichen" → `frank-adm/api.php?action=publish` triggert
   die GitHub-API (`repository_dispatch`) per `PUBLISH_TOKEN`.
2. Der Workflow lädt die **serveraktuelle** `data/site.json` (`action=sitejson`),
   baut mit Astro und deployed die statische Site + PHP-Reste per SFTP.
3. Ergebnis: Content-Änderung ist in ~1–2 min live (statt sofort) – bewusster
   Trade-off der statischen Basis. Details & Secrets: **TEMPLATE.md**.

> **Erster Deploy auf einen frischen Server:**
> Damit die Pipeline überhaupt funktioniert, müssen anfangs **nur** der
> `backup.php` und der Ordner `backup/` (inkl. `.htaccess` gegen öffentlichen
> Zugriff) auf dem Server liegen – alles andere übernimmt der Workflow beim
> ersten Merge. Der `BACKUP_URL` sollte danach sofort lieferbar sein
> (`https://<subdomain>/backup.php`).

**Nicht deployen:** `.env`, `dev/`, `*.md`, `node_modules/`, `.github/`,
`package-lock.json`, `data/admin.local.json` (Workflow-`exclude` bzw. bewusste
Auswahl im `publish.yml`).

---

## Kontaktformular

Das Formular (`sections/Contact.astro`) wird per `fetch()` **ohne Seiten-Reload**
an `api/kontakt.php` gesendet (Erfolg-/Fehlermeldung erscheinen oberhalb des
Formulars). Serverseitig liest `api/kontakt.php` die `site.json`-Config und
versendet die Anfrage per E-Mail.

- **Empfänger:** `config.email` – überschreibbar pro Umgebung über
  `contact.mailTo` (bzw. `data/kontakt.local.json` lokal, siehe unten).
- **Versandweg:** PHP `mail()` (Server-Postfix). Optional vorbereitet: SMTP
  unter `contact.mailer` (Host/Port/User/Passwort/Encryption) – sobald `host`
  gesetzt ist, geht der Versand über dieses Konto statt `mail()`.
- **Schutzmechanismen:** Honeypot-Feld `website` (client- + serverseitig),
  serverseitige Validierung aller Felder (Name, E-Mail, Telefon, Nachricht),
  422 + Feld-Fehler bei ungültigen Eingaben, nur `POST` erlaubt.

### Lokal testen (ohne eigenes Postfach)

Für lokale Tests wird der Empfänger über die **nicht versionierte** Datei
`data/kontakt.local.json` auf das lokale Postfix-Postfach umgebogen:

```json
{ "to": "ludwigmair@localhost" }
```

Die eintreffenden Mails landen dann in der Spool-Datei `/var/mail/<user>`:

```bash
tail -20 /var/mail/ludwigmair          # letzte Mail ansehen
mail -f /var/mail/ludwigmair           # interaktiv (q zum Beenden)
```

`data/kontakt.local.json` ist per `.gitignore` und Workflow-`exclude`
geschützt und wird nie committed/deployed.

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
