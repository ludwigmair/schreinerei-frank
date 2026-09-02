# Schreinerei Frank – Onepager

SEO-/KI-optimierte statische Single-Page-Website für die Schreinerei Frank, Seeon (Chiemgau).
Design: **Konzept C „Meisterhand"** – Details in [`DESIGN.md`](DESIGN.md).

**Statische Seite** (HTML/CSS/JS, Progressive Enhancement – Inhalte, FAQ und Formular
funktionieren auch ohne JavaScript), mit **Eleventy** als schlankem Renderer und
**Decap CMS** als git-basierter Redaktions-Oberfläche. Der Build hat keine Laufzeit,
das Ergebnis in `_site/` ist reines statisches HTML.

---

## Schnellstart

```bash
cd schreinerei-frank
npm install
npm run dev        # Seite: http://localhost:9999  ·  CMS: http://localhost:9999/admin/
```

`npm run dev` startet parallel:

- **Eleventy** (`--serve`, Port **9999**) – baut `src/` + `content/` nach `_site/`, Live-Reload
- **decap-server** (Port 8081) – lokaler CMS-Backend-Proxy, schreibt direkt in `content/site.json`

Im CMS auf **„Login"** klicken (lokal ohne Passwort) → *Website-Inhalte → Onepager*.
Speichern/Veröffentlichen schreibt `content/site.json`, Eleventy baut neu, der Browser lädt neu.

Einzelbefehle: `npm run build` (einmal bauen), `npm run serve` (nur Seite), `npm run cms` (nur CMS-Proxy).
Port ändern: in `package.json` → `scripts.serve` (`--port 9999`). decap-server bleibt auf 8081
(Decap erwartet den lokalen Backend-Proxy dort; nur ändern, wenn belegt – dann auch in
`src/admin/index.html` die `local_backend`-URL setzen).

**Voraussetzung:** Node ≥ 18 (getestet mit 24 und 26) + npm. Sonst nichts – kein globales Tool,
keine Datenbank, kein Account.

---

## Struktur

```
schreinerei-frank/
├── .eleventy.js            # Build-Konfig + JSON-LD-Generierung aus site.json
├── package.json            # scripts: dev / build / serve / cms
├── content/
│   └── site.json           # ALLE Inhalte (Texte, Listen, Bilder-Pfade) – das bearbeitet das CMS
├── src/
│   ├── index.njk           # Seiten-Template (Nunjucks), zieht Werte aus {{ site.* }}
│   ├── assets/
│   │   ├── css/styles.css   # ein Stylesheet, Tokens in :root
│   │   ├── js/main.js       # Nav, FAQ-Akkordeon, Galerie-Slider, Formular
│   │   └── img/*.svg        # PLATZHALTER-Bilder (klar beschriftet)
│   ├── admin/               # Decap-CMS: index.html + config.yml (Passthrough)
│   ├── llms.txt robots.txt sitemap.xml site.webmanifest
├── _site/                  # BUILD-OUTPUT – das wird deployt (nicht versionieren)
├── DESIGN.md  README.md
```

Wer **kein CMS** will, bearbeitet die Werte direkt in `content/site.json` (oder ersetzt
`src/index.njk` durch statisches HTML) – `npm run build` genügt.

---

## Wo liegt was (lokal)

| Zweck | Pfad |
|---|---|
| Projekt (dieses Repo) | `~/Sites/frank-test/schreinerei-frank/` |
| Bearbeitbare Inhalte | `schreinerei-frank/content/site.json` |
| Quellen (Template, CSS, JS, Bilder) | `schreinerei-frank/src/` |
| Fertige, deploybare Seite | `schreinerei-frank/_site/` (nach `npm run build`) |
| Abhängigkeiten (lokal, nicht deployen) | `schreinerei-frank/node_modules/` (via `npm install`) |
| Node (Runtime, nur lokal für Build/CMS) | Homebrew: `/opt/homebrew/bin/node` (v26) — oder nvm: `~/.nvm/versions/node/<ver>/bin/node` |

`node_modules/` und `_site/` stehen in `.gitignore` – ins Git kommen nur die Quellen +
`content/`. Der Build erzeugt `_site/` reproduzierbar neu.

---

## Deployment

Deploybar ist **nur der Ordner `_site/`** (nach `npm run build`). Reines statisches Hosting –
kein Node, kein PHP, keine Datenbank auf dem Server.

### Was du beim Hosting brauchst

| | Nötig? | Warum |
|---|---|---|
| Statisches Hosting (HTTPS) | **ja** | liefert `_site/` aus. Netlify, Cloudflare Pages, GitHub Pages, Vercel, oder klassischer Webspace/nginx/Apache |
| Node auf dem Server | nein | Build läuft vorab (lokal oder in der CI), Ergebnis ist HTML/CSS/JS |
| Build-Runner (CI) | empfohlen | Netlify/CF Pages bauen bei jedem Git-Push automatisch: Command `npm run build`, Publish-Dir `_site` |
| Git-Repo | für CMS-Betrieb: **ja** | Decap speichert Änderungen als Commit ins Repo → CI baut → live. Ohne Repo: CMS nur lokal, Deploy manuell |
| Auth-Provider fürs CMS | für CMS-Betrieb: **ja** | damit sich die Redaktion einloggen kann (Details unten). Für die reine Website irrelevant |
| Eigene Domain + DNS | ja (für Livegang) | A/CNAME auf den Hoster; `meta.siteUrl` in `content/site.json` darauf setzen |
| Formular-Dienst | ja, sobald das Kontaktformular live soll | statisches Hosting kann keine Mails senden – siehe „Kontaktformular anbinden" |
| SMTP / Mailserver | nein | übernimmt der Formular-Dienst |

Kürzestweg ohne CI: `npm run build` lokal → Inhalt von `_site/` per Drag-and-drop zu
Netlify/CF Pages oder per FTP auf den Webspace. Dann läuft das CMS nur lokal (`npm run cms`).

### Netlify / Cloudflare Pages (empfohlen, inkl. CMS-Login)

- Build command: `npm run build` · Publish directory: `_site`
- **Decap-Login der Redaktion:**
  - *Netlify:* Identity + Git Gateway aktivieren (`backend: git-gateway` steht in `config.yml`).
  - *Cloudflare Pages / GitHub:* in `src/admin/config.yml` auf `backend: { name: github, repo: "user/repo", branch: main }` umstellen und einen OAuth-Provider hinterlegen (z. B. `github` via Cloudflare Worker `decap-proxy`, oder Sveltia CMS mit eingebautem GitHub-OAuth).
- Nach jedem Speichern im CMS committet Decap nach `content/site.json` → CI baut → live.

### Login & Deployment (Weg 1: GitHub + Netlify) – Schritt für Schritt

Das CMS hat bei dieser Variante **kein eigenes Passwort** – die Redaktion meldet sich
über den GitHub-Login (OAuth) an. So kommst du dorthin:

1. **GitHub-Repo anlegen** (z. B. `schreinerei-frank`), Projekt pushen.
   Plattformen: `gh repo create schreinerei-frank --private --source=. --push`
   oder GitHub-Webseite → „New repository" → git remote + push.
2. **`base_url` nicht vergessen**: In Netlify erhältst du automatisch einen
   `github`-OAuth-Scope; `base_url: https://api.github.com` ist in der
   `src/admin/config.yml` bereits gesetzt. `repo: "USERNAME/REPO"` dort eintragen.
3. **Netlify-Site anlegen** → *Import an existing project* → dein GitHub-Repo.
   Build command `npm run build`, Publish directory `_site`.
4. **Identity aktivieren**: Netlify-Dashboard → *Site settings* → *Identity* → Enable.
5. **External provider**: *Site settings → Identity → Services → Git Gateway* aktivieren
   (dann `git-gateway`-Backend möglich) **oder** für GitHub-Login einen
   *External provider → GitHub* anlegen (OAuth-App, Client-ID/Secret).
6. **Admin öffnen**: `https://deine-site/admin/` → **„Login with GitHub"** (bzw. Netlify-Identity-Einladung).
   Nach erfolgreichem Login kannst du dort Inhalte bearbeiten; jedes Speichern committet
   nach `content/site.json`, Netlify baut neu und die Seite ist live.

> Hinweis: Wenn du nur die **fertige Webseite** per FTP hochladen und den Admin lokal
> (`npm run cms`) nutzen willst, bleibt der FTP-/Webspace-Weg unten weiterhin möglich –
> dann aber ohne Browser-Login auf dem Server.

### Beliebiger Webspace

`npm run build` lokal, Inhalt von `_site/` hochladen. Das CMS läuft dann nur lokal
(`npm run cms`) oder gar nicht.

### Empfohlene Header (Netlify `_headers` / Apache / nginx)

- `Cache-Control: public, max-age=31536000, immutable` für `assets/**`
- `Cache-Control: public, max-age=3600` für `index.html`
- `text/plain; charset=utf-8` für `llms.txt`
- Brotli/Gzip aktivieren

### Nach dem Livegang

1. Domain in `content/site.json` (`meta.siteUrl`) sowie `src/sitemap.xml`, `src/robots.txt`, `src/llms.txt` prüfen (aktuell `https://www.schreinerei-frank.de`).
2. **Google Search Console** – Property + Sitemap.
3. **Google-Unternehmensprofil** verknüpfen; NAP überall identisch (Seite, Impressum, Profil).
4. Rich-Results-Test: <https://search.google.com/test/rich-results>.

---

## Inhalte pflegen (Feldübersicht)

`content/site.json` → im CMS als „Onepager". Struktur:

| Block | Inhalt |
|---|---|
| `meta` | Title, Description, OG-Texte/-Bild, `siteUrl`, LCP-Bild |
| `business` | NAP, Fax, E-Mail, Öffnungszeiten (Text **und** strukturiert für Google), Geo, Einzugsgebiet |
| `nav` | Header-Menüpunkte |
| `hero` | Kicker, H1, Claim, Einleitung, 2 Buttons, Bild |
| `trust` | 4 Vertrauens-Punkte |
| `servicesIntro` + `services` | Leistungs-Überschrift + 6 Karten (Titel, Text, Icon-Pfad, Schema-Typ) |
| `galleryIntro` + `gallery` | Galerie-Bilder mit Bildunterschrift + Alt-Text |
| `about` | Text, Punkte-Liste, Ansprechpartner (Name, Rolle, Foto) |
| `faqIntro` + `faq` | Frage/Antwort-Paare → auch als `FAQPage`-JSON-LD |
| `testimonialsIntro` + `testimonials` | Zitat + Quelle |
| `contact` | Überschrift, Formular-`action`, Einwilligungstext |
| `footer` | Impressum-/Datenschutz-Links |

Die **strukturierten Daten (JSON-LD)** werden in [`.eleventy.js`](.eleventy.js) aus diesen
Werten generiert – Änderungen an NAP, Öffnungszeiten, Leistungen oder FAQ wandern
automatisch mit.

---

## Vor dem Launch zu erledigen (Platzhalter)

| Thema | To-do |
|---|---|
| **Bilder** | `src/assets/img/*.svg` durch echte Fotos ersetzen; Pfade in `content/site.json` anpassen. Dateinamen sind bereits SEO-sprechend. Pipeline s. u. |
| **OG-/Icon-Dateien** | `og-schreinerei-frank.jpg` (1200×630), `apple-touch-icon.png` (180×180), `icon-192.png`, `icon-512.png`, `logo-schreinerei-frank.png` in `src/assets/img/` anlegen. |
| **Öffnungszeiten** | Mo–Do 7:00–16:30 / Fr 7:00–13:00 sind angenommen – bestätigen (`business.openingHoursText` **und** `business.openingHoursSpec`). |
| **Geo-Koordinaten** | `business.geo` ist ca. Ortsmitte Seeon – exakte Werte eintragen. |
| **FAQ / Referenzen** | Texte sind plausible Vorschläge – durch echte ersetzen. |
| **Gründungsjahr / „seit …"** | Falls vorhanden, in Hero/Über-uns ergänzen (+ ggf. `foundingDate` im JSON-LD) – stärkt E-E-A-T. |
| **Formular-Backend** | `contact.formAction` (`/api/kontakt`) braucht einen Empfänger – s. u. |
| **Rechtstexte** | `/impressum` und `/datenschutz` verlinkt, aber noch nicht angelegt. |
| **Fonts** | aktuell Google-Fonts-CDN → self-hosten (s. u.). |

---

## Fonts self-hosten (CWV)

1. `Fira Sans` (400/500/600/700) + `Spectral` italic 400 als `woff2` (z. B. `google-webfonts-helper`).
2. Nach `src/assets/fonts/` legen, im CSS `@font-face` mit `font-display: swap`.
3. Wichtigste Schnitte vorladen: `<link rel="preload" as="font" type="font/woff2" href="/assets/fonts/fira-sans-600.woff2" crossorigin>`.
4. Die `fonts.googleapis.com`-Zeilen in `src/index.njk` entfernen.

---

## Bild-Pipeline

Je Motiv 3 Formate × 3 Breiten (640 / 960 / 1280 px):

```bash
for w in 640 960 1280; do
  cwebp -q 72 foto.jpg -resize $w 0 -o einbaukueche-wildeiche-seeon-$w.webp
  avifenc --min 24 --max 36 foto-$w.png einbaukueche-wildeiche-seeon-$w.avif
done
```

`<picture>`-Muster steht als Kommentar in `src/index.njk` über der Galerie.
Das Hero-Bild bleibt `fetchpriority="high"` + Preload im `<head>`.

---

## Kontaktformular anbinden

| Dienst | Kostenlos | Hinweis |
|---|---|---|
| **Web3Forms** | ✔ | `access_key` als hidden-field, kein Account |
| **Formspree** | ✔ (50/Monat) | `formAction` = `https://formspree.io/f/xxxx` |
| **Netlify Forms** | ✔ (100/Monat) | `data-netlify="true"` + hidden `form-name` |
| **Cloudflare Pages Functions** | ✔ | `functions/api/kontakt.js`, Mail über MailChannels/Resend |

Der Fetch-Zweig (JSON-Antwort → Erfolg/Fehler) ist in `src/assets/js/main.js` unten
auskommentiert vorbereitet; Honeypot-Feld (`website`) ist drin.

---

## Alternativen zum CMS-Backend

- **Sveltia CMS** – Drop-in-Ersatz für Decap (gleiche `config.yml`), schneller, eingebauter
  GitHub-/GitLab-OAuth. In `src/admin/index.html` nur das Script tauschen.
- **Pages CMS** (pagescms.org) – GitHub-App, kein eigener OAuth-Proxy nötig.
- **Vollwertige Headless-CMS** (Strapi, Directus, Payload, Sanity) – für einen Onepager
  Overkill (Server/DB/Hosting), aber dank sauberer Datenstruktur problemlos anschließbar:
  `content/site.json` gegen einen API-Fetch im Build (`.eleventy.js` `addGlobalData`) tauschen.

---

## Qualitäts-Checkliste

- [x] genau eine `<h1>`, saubere `h2`-Hierarchie, Landmarks (`header/nav/main/footer`)
- [x] JSON-LD (aus `site.json` generiert): `LocalBusiness`/`Carpenter`, `Organization`, `WebSite`, `WebPage`, `BreadcrumbList`, `FAQPage`, `OfferCatalog`
- [x] Meta-Title + Description, Open Graph, Twitter Card, `canonical`, `lang="de"`
- [x] `robots.txt` (+ KI-Bots), `sitemap.xml`, `llms.txt`
- [x] alle Bilder mit `alt`, `width`/`height` (kein CLS), Lazy-Load unter dem Fold
- [x] Fokus sichtbar, Skip-Link, Tastaturbedienung, `prefers-reduced-motion`
- [x] Galerie: L→R, 2→3→4 Bilder, Snap, Wischen/Pfeile/Tastatur, Autoplay mit Pause
- [x] CMS end-to-end getestet (Edit → `site.json` → Rebuild → Seite)
- [ ] echte Bilder + OG-/Icon-Assets
- [ ] Fonts self-hosted
- [ ] Formular-Backend
- [ ] Impressum + Datenschutz
- [ ] Lighthouse ≥ 95 in allen Kategorien (nach Bildtausch)
```
