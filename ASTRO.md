# ASTRO.md – Was fehlt noch? (Offener Stand der Astro-Migration)

Zwischenstand: **Phasen 0–4 implementiert und lokal auf `feature/astro`
committet** (Build grün, Inhalte paritätsgleich zur Live-PHP-Site). Was jetzt
noch fehlt, steht hier – nach „kann nur du“, „Abnahme/Switch“ und
„Qualität“ sortiert. Erledigte Optimierungen stehen am Ende („Doku +
CSS-Stand“).

---

## 0. CSS-Stand & Doku (erledigt, Stand 05/2026)

- **Einzige CSS-Datei:** `src/styles/global.css` (vorher
  `public/assets/css/styles.css`). Enthält alle Design-Tokens + Regeln
  (Referenz: `DESIGN.md`).
- **Einbindung:** `import '../styles/global.css'` in `src/layouts/Base.astro`
  → Astro/Vite bündelt sie zu einem minifizierten `dist/assets/*.css`
  (Cache-Hashing). Kein 1:1-Kopie-Pfad aus `public/` mehr; den separaten
  `<link>` in `Seo.astro` gibt es nicht mehr.
- **Optimierung:** ungenutzte Regeln entfernt (`.section--secondary`,
  Google-Rezensions-Block, redundante `.map__btn:hover .map__badge`).
  Bewusst behalten wurden `.js`/`.no-js` (HTML-Klassen-Swap in Base),
  `.legal__pre` (dynamisch gesetzt) und `.lightbox__thumb-btn` (per JS
  generiert) – dauerkontrolle via Klassen-Inventar (src + dist + main.js).
- **Commit:** `8c659fe` („CSS-Optimierung: ungenutzte Regeln entfernt, CSS via
  Astro-Bundle“).
- Für Projekte: Design-Anpassung immer in `src/styles/global.css` (`:root`),
  siehe `TEMPLATE.md` → „Rebranding“.

### Lokal bauen & testen (alles ohne Server/GitHub)

```bash
nvm use                    # Node 22 laden
npm run dev                # Dev-Server mit Hot-Reload (Port 4321; bei Konflikt: --port 4322)
npm run build              # statische Site nach dist/ (gitignored)
npm run preview -- --port 4322   # gebaute Site lokal ansehen
```

- `dist/` ist gitignored; der Build erzeugt 3 Seiten (index, impressum,
  datenschutz) + SEO-Dateien (`sitemap.xml`, `robots.txt`, `llms.txt`,
  `site.webmanifest`) + assets.
- **Dev vs. Build:** Im Dev-Modus serviert Vite das CSS aus
  `src/styles/global.css` inline (ein direkter Abruf wie `/global.css` liefert
  darum 404 – normal). Erst `npm run build` bündelt+minifiziert zu
  `dist/assets/Base.*.css` (gehast, Cache-freundlich).
- **Publish-Pfad lokal simulieren:** admin speichern (`data/site.json` via
  Admin) → `?action=sitejson` → `npm run build` → `_deploy/`-Zusammenstellen →
  `npm run preview`. Exakte Etappen: `TEMPLATE.md` → „Lokal testen“.
  Nicht lokal simulierbar: GitHub-Dispatch, Backup, SFTP.

---

## 1. Secrets & Zugänge (kann nur du, ich habe keine Zugriffe)

Der komplette Publish-Flow ist gebaut, aber **noch nicht scharf geschaltet**,
weil Tokens/Zugangsdaten fehlen.

### a) Server `.env` (Webspace, Datei ist gitignored)

Ergänze im Root des Repos auf dem Server:

```bash
SITEJSON_TOKEN=<zufälliges langes Token>
PUBLISH_TOKEN=<GitHub-PAT mit repo-Schreibrecht>
```

- `SITEJSON_TOKEN` – erlaubt dem Build-Workflow, die aktuelle `data/site.json`
  abzuholen (`frank-adm/api.php?action=sitejson&token=…`).
- `PUBLISH_TOKEN` – Personal Access Token (fine-grained, repo content read/write),
  mit dem der Admin `repository_dispatch` triggert. Erzeugen unter
  GitHub → Settings → Developer settings → Personal access tokens.
- Auf EXISTIERENDE KEYs achten: `.env` enthält schon `ADMIN_USERS` – nicht
  überschreiben, nur zwei Zeilen ergänzen.

### b) GitHub Actions Secrets (Settings → Secrets and variables → Actions)

| Secret           | Wert                                                        | Aufwand |
|------------------|-------------------------------------------------------------|---------|
| `SITE_BASE`      | Sub-Domain des Webspace, z. B. `https://schreinerei-frank-astro.typopublic.com` | neu |
| `SITEJSON_TOKEN` | identisch zur `.env`-Zeile                                   | neu     |
| `FTP_SERVER` / `FTP_USERNAME` / `FTP_PASSWORD` / `FTP_TARGET_DIR` | bestehend (aus Deploy) | vorhanden |
| `BACKUP_URL` / `BACKUP_TOKEN`  | bestehend (aus Deploy)                                | vorhanden |

### c) Branch pushen

`feature/astro` ist bewusst **lokal** – für den ersten echten Publish-Test
musst du den Branch pushen (und später mergen).

```bash
git push -u origin feature/astro
```

---

## 2. Publish-Test auf der Astro-Subdomain (vor dem Switch)

Der Test läuft NEBEN der Live-Site – Basis ist die eigene Subdomain
`schreinerei-frank-astro.typopublic.com` (im `astro.config.mjs` bereits gesetzt).

> **Vorschaltbar:** Die Etappen „Admin speichern“ und „site.json → build →
> `_deploy`“ kannst du **lokal komplett simulieren** (ohne Secrets), inkl.
> `action=sitejson`. Exakt dokumentiert in `TEMPLATE.md` → „Lokal testen“.
> Was lokal nicht geht: GitHub-Dispatch, Backup, SFTP.

1. **Secrets setzen** (Punkt 1a/b), Branch pushen (1c).
2. **Erst-Deploy auf die Subdomain:** einmal `deploy-staging.yml` (oder manuell
   SFTP) mit .htaccess/`backup.php`/`frank-adm`/`assets` vorab ausliefern, damit
   der Workflow den `SITE_BASE`-Crawl und den Backup-Call bedienen kann.
   → siehe „Erster Deploy auf einen frischen Server“ in README/WORKFLOW.
3. **Workflow manuell testen:** GitHub → Actions → „Astro publish“ →
   `Run workflow` (workflow_dispatch) gegen `SITE_BASE` = Subdomain.
   - Prüfen: step „site.json abholen“ ok, Build läuft durch, SFTP ok,
     Backup ok.
4. **Admin-Klick testen:** auf der Subdomain `/frank-adm/` einloggen →
   „Veröffentlichen“ drücken → GitHub-Run startet automatisch.
5. **Inhalte verifizieren:** eine Änderung speichern → veröffentlichen →
   kontrollieren, dass die Subdomain den neuen Stand zeigt (~1–2 min).
6. **Bekannte Trade-offs prüfen:** Statusbox `?kontakt=ok|fehler` nur mit JS
   (kein no-JS-Fallback), sofort-live geht nicht mehr.

Fehlschläge bitte herumdokumentieren (Fehlermeldung!) – ich fixe sie.

---

## 3. Abnahme & Switch (Phase 5)

- [ ] Paritäts-Check Subdomain vs. Live: Home-Sektionen, Impressum,
      Datenschutz, SEO-Dateien (`/sitemap.xml`, `/robots.txt`, `/llms.txt`,
      `/site.webmanifest`), Kontakt-Formular, Backup-Aufruf.
- [ ] `feature/astro` → `develop` zusammenführen, danach `develop` → `main`.
- [ ] Nach dem Switch: **alte PHP-Seite aus dem Web-Root entfernen**
      (`index.php`, `impressum/`/`datenschutz/`-PHP-Ordner, `php/`, `sitemap.php`,
      `robots.php`, `llms.php`, `webmanifest.php`, `js/`, Root-`assets/`).
- [ ] `.htaccess`-Endkontrolle: liefert statische Seiten; nur `frank-adm/`,
      `api/kontakt.php`, `backup.php` bleiben PHP; `data/` bleibt gesperrt.
- [ ] **Backup-Retention** verifizieren (letzte 5 Zips bleiben).
- [ ] Suchmaschinen: Search-Console-Crawl + Sitemap-Anmeldung gegen neue
      static-served Dateien; Rich-Results-Test erneut.

---

## 4. Qualität / Restpunkte

Aus der alten Qualitäts-Checkliste noch offen:

- [ ] **Fonts self-hosten.** Aktuell Google-Fonts-CDN in
      `src/components/seo/Seo.astro` (Zeile ~54). Für das Template-Prinzip
      (kein externes CDN, Datenschutz) Fonts lokal nach `public/assets/fonts/`
      legen und per `@font-face`/`link` einbinden.
- [ ] **Echte Bilder + OG-/Icon-Assets final prüfen.** Pfade sind korrekt
      verdrahtet (`/assets/site/og.jpg`, `icon-192/512`, `apple-touch-icon`),
      aber der tatsächliche Bildinhalt ist der Original-Bestand –
      für ein sauberes Template ggf. Beispiel-Assets markieren.
- [ ] **Kontakt-Formular-Backend endgültig anbinden.** Inhalt (mailTo/Mailer)
      ist aus site.json; echter Versand erfolgt erst auf dem Hoster. Lokalen
      Test via `data/kontakt.local.json` → `/var/mail`.
- [ ] **Impressum-/Datenschutz-Texte final prüfen** (Rechtstexte, aktueller
      Betreiber/Anschrift/E-Mail).
- [ ] **`about.heading`-Fallback:** „Geht ned gibt's neda“ kommt aus
      `data/site.json` und wird nur gerendert, wenn `About.astro` das Feld
      liest – bei einem Zweitprojekt bewusst eigene Texte setzen.

---

## 5. Nach Abschluss (Doku ins Reine bringen)

- [ ] Dieser Punkt ist erledigt, sobald Abschnitt 1+2 (Secrets + Subdomain-Publish)
      durchgelaufen sind: die Offen-Punkte hier entfernen und `ASTRO.md` auf das
      Nötigste kürzen.
- [ ] `TEMPLATE.md` um die real getestete Secrets-/Workflow-Konfiguration
      ergänzen (weitgehend schon dokumentiert).
- [ ] Historischer Migrations-/Detailstand (alte PHP-Portierungen) ist obsolet –
      vor dem Löschen eventuell in `TEMPLATE.md` konsolidieren.

---

## Auf einen Blick

| Bereich | Status |
|---|---|
| Astro-Scaffold, Daten-Port, Layout, Sektionen | ✅ fertig (Parität geprüft) |
| Seiten index/impressum/datenschutz | ✅ fertig |
| SEO-Dateien als Build-Output | ✅ fertig (identisch verifiziert) |
| .htaccess (statisch), config_sync aus | ✅ fertig |
| Admin publish/sitejson + Button | ✅ Code fertig, ⏳ Test offen |
| publish.yml Workflow | ✅ Code fertig, ⏳ Test offen |
| Secrets (`.env` + GitHub) | ⏳ nur du |
| Subdomain-Test + Abnahme/Switch | ⏳ nach Secrets |
| Fonts self-hosten u. a. Qualitätspunkte | ⏳ offen |