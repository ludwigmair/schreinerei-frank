# Astro-Template-Plan: Schreinerei Frank → statische Example-Site

Ziel: Aus der aktuellen PHP-Seite eine **wiederverwendbare Template-Website** machen:
schneller (Build-Time statt PHP), pflegeleichter (Inhalte datengetrieben, Komponenten
austauschbar) und pro Projekt nur über `config`-Werte rebrandbar. Content-Pflege
bleibt im Web-Admin (Hybrid), Veröffentlichen per Klick.

---

## 1. Prinzipien

1. **Content-Wahrheit bleibt `data/site.json`** – editiert durch den PHP-Admin auf dem
   Server (sofort wirksam, wie heute).
2. **Build = Astro (statisches HTML, kein PHP zur Laufzeit)** – bei „Veröffentlichen"
   baut eine GitHub Action mit der **serveraktuellen** site.json und deployed per SFTP.
3. **Nur saubere Reste bleiben PHP** auf dem Hoster: Admin, `api/kontakt.php`,
   `backup.php` – alles andere ist statisch.
4. **Template-Pflicht:** Alle Inhalte kommen aus site.json; kein einziger
   kundenbezogener Text hart im Astro-Code. Komponenten sind austauschbare `.astro`-Dateien.
5. **Dev bleibt lokal ohne Server:** `npm run dev` liefert die Seite aus der
   Repo-site.json – identische Sektionen, Hot-Reload.

---

## 2. Ist-Zustand (heute: PHP, alles serverseitig)

| Bereich | Dateien | Inhalt |
|---|---|---|
| Rendering | `index.php`, `php/header.php`, `php/home.php`, `php/legal.php`, `php/footer.php` | Router + Partials aus site.json |
| Datenebene | `php/data.php`, `php/config.php`, `data/site.json` | site.json lesen, `{config.X}`-Resolve, Build-Dateien |
| SEO | `php/seo.php`, `sitemap.php`, `robots.php`, `llms.php`, `webmanifest.php`, `seo.php` | Head/OG/Twitter/JSON-LD + dynamische SEO-Dateien |
| Frontend-JS | `js/main.js` (690 Z.) | Slider, Galerie/Lightbox, Testimonials, FAQ, Cookie, Karten-Dialog, Kontakt-Formular |
| Admin | `frank-adm/` (index, api, helpers, upload, admin.js/css) | Login, generischer Schema-Editor auf site.json, Bild-Upload |
| Kontakt | `api/kontakt.php` | Validierung + `mail()`, JSON/PRG-Fallback |
| Backup/Deploy | `backup.php`, `.github/workflows/deploy-staging.yml` | ZIP-Backup, SFTP-Deploy bei develop→staging-Merge |
| Dev | `dev/serve.sh`, `dev/router.php`, `dev/users.php` | lokaler PHP-Server + Chrome |

**Kern-Aufwand der Migration:** `php/*`-Templates → Astro, `seo.php` → SEO-Komponente,
SEO-Endpunkte → Build-Time, Deploy-Flow → Publish-Flow.

---

## 3. Ziel-Architektur (Hybrid)

```
feature/astro  (neue Basis, werden dann develop/main)
├─ astro.config.mjs          # build.outDir = dist/, Content-Collections
├─ package.json              # astro, @astrojs/*, dev/build/preview
├─ src/
│  ├─ layouts/Base.astro     # <html>, Head/Seo, Header, Footer, Cookie-Banner
│  ├─ components/
│  │  ├─ seo/Seo.astro       # Titel/OG/Twitter/JSON-LD (portiert aus seo.php)
│  │  ├─ sections/           # Hero, Services, Gallery, About, Faq, Testimonials, Contact …
│  │  └─ ui/                 # Slider, Lightbox, Accordion, Buttons, Statusbox …
│  ├─ pages/
│  │  ├─ index.astro         # Startseite
│  │  ├─ impressum.astro
│  │  └─ datenschutz.astro
│  └─ lib/
│     ├─ data.ts             # site.json laden + {config.X}-Resolve (Port von data.php)
│     ├─ seo.ts              # JSON-LD @graph (Port von seo.php)
│     └─ content.ts          # Content-Collection-Unterstützung
├─ public/
│  ├─ assets/                # css, img, content, site (unverändert deployen)
│  └─ (generiert) sitemap.xml, robots.txt, llms.txt, site.webmanifest
├─ admin/                    # bleibt als PHP: frank-adm + api/kontakt.php + backup.php
├─ data/site.json            # Content-Wahrheit (auch in repo, für Dev)
└─ .github/workflows/publish.yml   # neuer Publish-Flow (ersetzt deploy-staging)
```

**Deploy setzt nur das zusammen:** `dist/` (statische Astro-Seite) + `frank-adm/` +
`api/kontakt.php` + `backup.php` + `public/assets/` auf den Web-Root.

---

## 4. Komponenten-Abbildung (austauschbar)

Jede Sektion von home.php wird eine eigene `.astro`-Komponente unter `sections/`.
„Austauschbar" heißt: Reihenfolge/Layout pro Projekt änderbar, ohne PHP – die Daten
bleiben identisch (gleiche Keys aus site.json).

| PHP-Block (ist) | Astro-Komponente (Ziel) |
|---|---|
| Hero + Slider (home.php) | `sections/Hero.astro` + `ui/Slider.astro` |
| Trust-Row | `sections/Trust.astro` |
| Services (+Intro) | `sections/Services.astro` |
| Galerie/Lightbox | `sections/Gallery.astro` + `ui/Lightbox.astro` |
| Über uns | `sections/About.astro` |
| FAQ | `sections/Faq.astro` + `ui/Accordion.astro` |
| Rezensionen | `sections/Testimonials.astro` + `ui/Slider.astro` |
| Kontakt + Formular | `sections/Contact.astro` + `ui/ContactForm.astro` |
| Footer/Cookie/Karte-Dialog | `Footer.astro`, `ui/CookieBanner.astro`, `ui/MapDialog.astro` |
| Impressum/Datenschutz | eigene Seiten, teilen `ui/LegalSections.astro` |
| Head/JSON-LD | `seo/Seo.astro` + `lib/seo.ts` |
| Banner/Status | `ui/StatusBox.astro` (PRG-Parameter `?kontakt=ok|fehler`) |

**JS:** `js/main.js` wird in Modul-Skripte pro Bereich zerlegt (oder eine Datei je
Sektion), Astro handled Bundling + Cache-Hashing – ersetzt das manuelle `?ver=2.4.2`.

---

## 5. Publish-Flow (der zentrale neue Teil)

Ablauf für „Inhalte sichern + neu bauen + live":

1. **Admin:** Button „Veröffentlichen" → ruft `frank-adm/api.php?action=publish`
   (Server-Secret-Token im Admin hinterlegt).
2. **Server:** Endpoint triggert per GitHub-API `repository_dispatch` (Personal Access
   Token als Server .env / admin.local.json, nicht versioniert).
3. **Workflow `publish.yml`:**
   - Checkout Repo,
   - lädt **aktuelle site.json vom Server** ab (geschützter Endpoint
     `frank-adm/api.php?action=sitejson` mit Token),
   - `npm ci && npm run build` (Astro liest die heruntergeladene Datei),
   - SFTP-Deploy (bestehende Action/Secrets wiederverwenden) inkl.
     Admin/`api/kontakt.php`/`backup.php`/`assets`,
   - Backup-Aufruf vor Deploy (wie heute).
4. **Ergebnis:** Content-Änderung ist nach ~1–2 min live (statt sofort) – akzeptierter
   Trade-off zugunsten statischer Basis.

**Dev/Repo-Flow:** merge `feature/astro` → develop → staging bleibt für Code-Änderungen;
der neue publish-Workflow ersetzt den reinen Staging-Deploy (oder ergänzt ihn).

**Test-Strategie:** Die Astro-Entwicklung wird zuerst parallel auf einer **eigenen
Sub-Domain** (`schreinerei-frank-astro.typopublic.com`) Deployed und getestet –
die Live-Site auf `schreinerei-frank.typopublic.com` bleibt bis zur Abnahme
(Phase 5) unberührt.

**Sicherheit:** beide Endpunkte (publish, sitejson) nur mit gültigem Token; Token in
GitHub Secrets (Workflow) bzw. Server-Datei `.env`/`admin.local.json` (gitignored).

---

## 6. Template-/Rebranding-Parameter (pro Projekt nur config ändern)

Alles Konfigurierbare steckt bereits im `config`-Block von `data/site.json`:

- `name`, `domain`, `email`, `phone`/`fax` (inkl. Schema-Formate)
- `themeColor`, `assetsBase`, `logo`, `adminPath`, `port`
- SEO-Textblöcke (`meta`, `legal`), alle Textsektionen, Navigation, Galerie

Für ein neues Projekt:
1. Repo klonen, `data/site.json`-Config anpassen,
2. `adminPath` ggf. ändern (generiert .htaccess/Admin-Pfad automatisch),
3. `assets/` (Logo, og.jpg, content) ersetzen,
4. lokale Vorschau `npm run dev`, dann normaler Git/CI-Flow.

Doku dazu wird als `TEMPLATE.md` gepflegt (checklisten-artig).

---

## 7. Migrationsphasen (feature/astro → develop/main)

### Phase 0 – Basisschablone ✅
- [x] `npm create astro`-Scaffold, `astro.config`, `src/lib/data.ts` (Port `{config.X}`-Resolve).
- [x] `public/assets/` aus Repo kopieren, `data/site.json` übernehmen.
- [x] `Base.astro`-Layout: Header/Footer/Cookie aus header.php/footer.php.

### Phase 1 – Startseite Sektionen ✅
- [x] Hero/Slider, Trust, Services, Gallery/Lightbox, About, FAQ, Testimonials.
- [x] SEO-Head & JSON-LD (`lib/seo.ts`, `Seo.astro`) pixel-/anspruchgleich zu heute,
      inkl. Preload/LCP, Fonts, OG.
- [x] Paritätscheck gegen Live-PHP (Sektionen-Zähler, Lücken-Audit).

### Phase 2 – Kontakt + Legal + Assets ✅
- [x] `Contact.astro` (fetch auf `/api/kontakt`, Statusbox via `?kontakt=ok|fehler`),
      `impressum.astro`, `datenschutz.astro`.
- [x] CSS übernommen (aus `public/assets/css/styles.css`); Slider/Lightbox/FAQ-JS
      aus portiertem `src/scripts/main.js`.

### Phase 3 – Generated SEO-Dateien ✅
- [x] `sitemap.xml`, `robots.txt`, `llms.txt`, `site.webmanifest` als Build-Output
      (`src/pages/*.ts`-Endpoints), statt PHP-Request. Inhalte paritätsgleich zur
      Live-PHP-Variante verifiziert.

### Phase 4 – Publish-Flow + Deploy-Ersatz ✅ (Infrastruktur gebaut; Secrets fehlen noch)
- [x] Server-Endpoints `api.php?action=sitejson` (liefert aktuelle site.json) und
      `api.php?action=publish` (triggert GitHub repository_dispatch) –
      token-geschützt, ohne Admin-Session nutzbar. Lokal verifiziert (401/500-Pfade).
- [x] Token-Helfer (`adm_env`) in `frank-adm/includes/helpers.php`, liest `.env`.
- [x] `.github/workflows/publish.yml` (repository_dispatch + workflow_dispatch):
      site.json abholen → `npm ci && npm run build` → `_deploy/` zusammenstellen
      (dist + frank-adm + api/kontakt + backup + .htaccess) → Backup → SFTP.
- [x] `.htaccess` auf Statik getrimmt (Verzeichnisse → `index.html`; Kontakt bleibt
      PHP; keine SEO-PHP-Rewrites mehr). `config_sync()` in `php/config.php` liefert
      nichts mehr (keine .htaccess/package.json-Autogen).
- [x] `TEMPLATE.md` (Rebranding-Anleitung, Secrets-Liste), README-Update.
- [ ] **Offen (auf dem Server/GitHub):** neue Secrets `SITEJSON_TOKEN`,
      `PUBLISH_TOKEN` (`.env`), `SITE_BASE` + `SITEJSON_TOKEN` (GitHub), Admin-UI-
      Button „Veröffentlichen", Publish-Test auf der Astro-Subdomain.

### Phase 5 – Abnahme & Switch (noch offen)
- [ ] Paritäts-Check Staging (Home, Legal, SEO-Quellen, Formular, Backup),
- [ ] feature/astro → develop → main, alte PHP-Seite aus Deploy entfernen, `.htaccess`
      auf statischen Root trimmen (PHP-Ordner geschützt, nur Admin/Kontakt/Backup reaktiv).

---

## 8. Risiken & offene Punkte

| Risiko | Mitigation |
|---|---|
| Sofort-Live-Editing geht verloren | Publish-Button im Admin (1–2 min) – dokumentiert, bewusst gewählt |
| `mail()` hängt am Hoster (Typopublic) | bleibt als PHP-Endpoint, nur `/api/kontakt.php` – kein Provider-Wechsel |
| WebP/AVIF jetzt serverseitig, künftig Build-time | `astro:assets` übernimmt Resizing/Format; Upload im Admin bleibt |
| Asset-Pfade (absolute `/assets/...`) | in Astro via `public/` beibehalten; site.json-Referenzen unverändert |
| Cookie/Formular-JS-Verhalten | selben `data-*`-Selektoren portieren, JS wörtlich übernehmen, nur Aufteilung |
| Admin-Editor erwartet PHP-Struktur | `frank-adm/` bleibt bytegleich; nur `publish`/`sitejson`-Endpunkte neu |
| `.htaccess` Rewrite-Entfall | statische Dateien direkt; nur PHP-nahe Pfade weiter via Rewrite |

**Phase-4-Entscheidungen (getroffen):**
- Neue Secrets: `SITEJSON_TOKEN` + `PUBLISH_TOKEN` (Server-`.env`), `SITE_BASE`
  + `SITEJSON_TOKEN` (GitHub). Beide Server-Endpunkte akzeptieren jedes der
  Tokens (Build-Workflow braucht nur `sitejson`; der Admin nur `publish`).
- `deploy-staging.yml` bleibt **ergänzend** bestehen (Code/Branch-Flow);
  `publish.yml` ist der neue Content-Flow.
- `adminPath` bleibt wie bisher über `config.adminPath` konfigurierbar; die
  `.htaccess` wird im Astro-Modus nicht mehr automatisch generiert
  (`config_sync()` aus), sondern liegt fix im Repo (= Template-Doku).

---

## 9. Datei-Checkliste (Migration)

- [x] `astro.config.mjs`, `package.json`, `tsconfig.json`
- [x] `src/lib/data.ts`, `src/lib/seo.ts` (content.ts optional, aktuell nicht nötig)
- [x] `src/layouts/Base.astro`
- [x] `src/components/seo/Seo.astro`
- [x] `src/components/ui/: Slider, Lightbox, Accordion, CookieBanner, MapDialog, StatusBox`
      (in den jeweiligen Sektionen integriert; kein eigener ui-Ordner nötig)
- [x] `src/components/sections/: Hero, Trust, Services, Gallery, About, Faq, Testimonials, Contact`
- [x] `src/pages/: index, impressum, datenschutz`
- [x] `src/scripts/main.js` (portiert; Astro-Bundling + Cache-Hashing)
- [x] `public/: assets/, favicon.ico` (+ `src/pages/*.ts` für webmanifest/sitemap/robots/llms)
- [x] `frank-adm/: publish + sitejson Endpoints`
- [x] `.github/workflows/publish.yml` (+ Secrets-Doku in `TEMPLATE.md`)
- [ ] `TEMPLATE.md` ✅ angelegt – README-Update ✅ – dieses File (→ Stand oben aktualisiert)