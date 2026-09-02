# Schreinerei Frank – Onepager (statisch)

SEO-/KI-optimierte Single-Page-Website für die Schreinerei Frank, Seeon (Chiemgau).
Design: **Konzept C „Meisterhand"** – Details in [`DESIGN.md`](DESIGN.md).

**Vollständig statische Website** (HTML/CSS/JS) ohne Server und ohne Build-Schritt:
Alle Inhalte liegen als `data/site.json` vor und werden **zur Laufzeit per JavaScript**
in die statischen HTML-Seiten gerendert. Änderst du die `site.json`, ist die Änderung
beim nächsten Seitenaufruf sofort sichtbar. Es gibt **keine Abhängigkeiten** (kein
Express, kein Eleventy, keine npm-Pakete) – reines Node für einen simplen lokalen
Dev-Server, auf dem Server selbst ist nichts nötig.

> **Hinweis (wichtig):** Weil die Inhalte clientseitig gerendert werden, funktioniert
> die Seite **ohne JavaScript nur eingeschränkt**. Für maximale SEO empfehlen wir, die
> exakt gleichen Inhalte serverseitig als statisches HTML auszuliefern, sofern du auf
> einem Build-Schritt bestehen möchtest. Alternativ bleibt die `site.json` als
> Single-Source-of-Truth erhalten und kann von einem Crawler/Static-Site-Generator
> zu reinem HTML vorgerendert werden.

---

## Schnellstart

```bash
cd schreinerei-frank
npm run serve        # http://localhost:9999 · Admin: /admin/
```

Verfügbare Befehle:

- `npm run serve` – startet einen schlanken statischen Dev-Server (Node, ohne Dependencies)
- `/admin/` – **Passwort-geschützter Editor, der nur lokal funktioniert** (siehe unten)

**Voraussetzung:** Node ≥ 18 (getestet mit 24 und 26). Sonst nichts – kein globales Tool,
keine Datenbank, kein Build, kein Account.

---

## Struktur

```
schreinerei-frank/
├── package.json               # nur "npm run serve" (statischer Dev-Server)
├── serve.js                   # Node-HTTP-Dateiserver (ohne Dependencies)
├── public/                    # DAS ist die deploybare Statische Website
│   ├── index.html             # Onepager
│   ├── impressum/index.html
│   ├── datenschutz/index.html
│   ├── admin/index.html       # lokaler Inhalte-Editor (Export nach site.json)
│   ├── data/
│   │   ├── site.json          # ALLE Inhalte – das bearbeitest du (Single-Source)
│   │   └── admin.json         # Admin-Benutzer-Hashes (nur für den lokalen Editor)
│   ├── js/
│   │   ├── render.js          # liest data/site.json und befüllt die Seite per JS
│   │   └── main.js            # Interaktion: Nav, FAQ, Slider, Lightbox, Formular …
│   ├── assets/
│   │   ├── css/styles.css
│   │   └── img/*.{png,jpg,webp,svg}
│   ├── llms.txt  robots.txt  sitemap.xml  site.webmanifest
├── DESIGN.md  README.md
```

---

## Inhalte bearbeiten

Alle Texte, Listen, Bilder-Pfade, Öffnungszeiten, FAQ usw. stehen zentral in
**`public/data/site.json`**. Änderungen dort sind beim nächsten Aufruf der Seite
sofort wirksam – **kein Build nötig**.

### JSON-Struktur (Kurzübersicht)

| Block | Inhalt |
|---|---|
| `meta` | Title, Description, OG-Texte/-Bild, `siteUrl`, LCP-Bild |
| `business` | NAP, Fax, E-Mail, Öffnungszeiten (Text **und** strukturiert für Google), Geo, Einzugsgebiet |
| `nav` | Header-Menüpunkte |
| `hero` | Kicker, H1, Claim, Einleitung, 2 Buttons, Bild(er) |
| `trust` | 4 Vertrauens-Punkte |
| `servicesIntro` + `services` | Leistungs-Überschrift + 6 Karten (Titel, Text, Icon-Pfad) |
| `galleryIntro` + `gallery` | Galerie-Kategorien mit Hauptbild + voller Bildliste |
| `about` | Text, Punkte-Liste, Ansprechpartner (Name, Rolle, Foto) |
| `faqIntro` + `faq` | Frage/Antwort-Paare → auch als `FAQPage`-JSON-LD |
| `testimonialsIntro` + `testimonials` | Zitat + Quelle |
| `contact` | Überschrift, Formular-`action`, Einwilligungstext |
| `footer` | Impressum-/Datenschutz-Links |

Die **strukturierten Daten (JSON-LD)** (`LocalBusiness`/`Carpenter`, `Organization`,
`WebSite`, `WebPage`, `BreadcrumbList`, `FAQPage`, `OfferCatalog`) werden in
`js/render.js` aus `site.json` generiert – Änderungen an NAP, Öffnungszeiten,
Leistungen oder FAQ wandern automatisch mit.

---

## So funktioniert das Rendern (JS)

- Jede statische HTML-Seite enthält **`<template data-list="…">`-**Blöcke für
  wiederkehrende Elemente (Leistungen, Galerie, FAQ, Rezensionen, Trust, Hero-Bilder)
  und **`data-bind`-Attribute** für einzelne Textschnipsel.
- `js/render.js` holt `data/site.json` per `fetch`, schreibt Titel/Description/
  Canonical/OG/JSON-LD in den `<head>` und füllt die Platzhalter + Listen.
- Danach wird `js/main.js` geladen – die Slider/Lightbox/FAQ laufen auf dem fertigen DOM.
- Eigene Pfad-Helfer: nicht vorhandene Werte werden einfach leer gelassen (kein Absturz).

---

## Admin (lokaler Inhalte-Editor)

`/admin/` ist ein **passwortgeschützter Editor ohne externen Dienst**. Er läuft **nur
lokal** über `npm run serve` und exportiert die bearbeiteten Inhalte als `site.json`
(Download). Diesen Stand ersetzt du in `public/data/site.json`.

- **Login:** Benutzer + Passwort. Die Zugangsdaten stehen als **SHA-256-Hashes** in
  `public/data/admin.json` (`admin`, `frank` …). Hash erzeugen:
  `echo -n "passwort" | shasum -a 256`.
- Bilder-Upload: funktioniert lokal über den Editor (Dateien werden nicht serverseitig
  abgelegt, sondern in die JSON eingebettet bzw. als Pfad gesetzt).

> **Sicherheitswarnung:** Der Editor und die Passwort-Hashes liegen **in der
> deploybaren `public/`-Struktur** und sind damit für jeden im Browser einsehbar.
> Das ist für einen echten, öffentlich erreichbaren Website-Admin **nicht sicher**.
> Nutze den Editor ausschließlich lokal zum Pflegen und Lade niemals `admin/` mit
> echten Passwort-Hashes auf einen öffentlichen Server hoch (oder entferne `admin/`
> vor dem Deploy). Alternativ: Hashes leer lassen und Inhalte direkt in
> `public/data/site.json` bearbeiten.

---

## Deployment

Deploybar ist **komplett der Ordner `public/`** – reines statisches Hosting. Kein Node,
kein PHP, keine Datenbank, kein Build auf dem Server.

| | Nötig? | Warum |
|---|---|---|
| Statisches Hosting (HTTPS) | **ja** | liefert `public/` aus (Beliebiges: Cloudflare Pages, GitHub Pages, Vercel, nginx/Apache, Webspace …) |
| Node/Build auf dem Server | nein | es gibt keinen Build |
| Formular-Dienst | ja, sobald das Kontaktformular live soll | statisches Hosting kann keine Mails senden – siehe unten |

1. Inhalte in `public/data/site.json` anpassen.
2. Inhalt von `public/` auf das statische Hosting hochladen.
3. Domain in `site.json` (`meta.siteUrl`) sowie `sitemap.xml`, `robots.txt`,
   `llms.txt` prüfen (aktuell `https://www.schreinerei-frank.de`).
4. **Search Console** Property + Sitemap; **Google-Unternehmensprofil** verknüpfen;
   Rich-Results-Test: <https://search.google.com/test/rich-results>.

### Empfohlene Header (Hoster / nginx / Apache)

- `Cache-Control: public, max-age=31536000, immutable` für `assets/**`
- `Cache-Control: public, max-age=3600` für `index.html` und `data/site.json`
- `text/plain; charset=utf-8` für `llms.txt`
- Brotli/Gzip aktivieren

---

## Kontaktformular anbinden

| Dienst | Kostenlos | Hinweis |
|---|---|---|
| **Web3Forms** | ✔ | `access_key` als hidden-field, kein Account |
| **Formspree** | ✔ (50/Monat) | `formAction` = `https://formspree.io/f/xxxx` |
| **Cloudflare Pages Functions** | ✔ | `functions/api/kontakt.js`, Mail über MailChannels/Resend |

Setze `contact.formAction` in `public/data/site.json` entsprechend. Der Fetch-Zweig
(JSON-Antwort → Erfolg/Fehler) ist in `js/main.js` unten auskommentiert vorbereitet;
das Honeypot-Feld (`website`) ist vorhanden.

---

## Qualitäts-Checkliste

- [x] genau eine `<h1>`, saubere `h2`-Hierarchie, Landmarks (`header/nav/main/footer`)
- [x] JSON-LD (aus `site.json` generiert): `LocalBusiness`/`Carpenter`, `Organization`, `WebSite`, `WebPage`, `BreadcrumbList`, `FAQPage`, `OfferCatalog`
- [x] Meta-Title + Description, Open Graph, Twitter Card, `canonical`, `lang="de"`
- [x] `robots.txt` (+ KI-Bots), `sitemap.xml`, `llms.txt`
- [x] alle Bilder mit `alt`, `width`/`height`, Lazy-Load unter dem Fold
- [x] Fokus sichtbar, Skip-Link, Tastaturbedienung, `prefers-reduced-motion`
- [ ] **No-JS-Fallback** – bei reinem Client-Rendering leer. Für maximale SEO optional in statisches HTML vorrendern.
- [ ] echte Bilder + OG-/Icon-Assets
- [ ] Fonts self-hosted
- [ ] Formular-Backend
- [ ] Impressum-/Datenschutz-Texte final prüfen
