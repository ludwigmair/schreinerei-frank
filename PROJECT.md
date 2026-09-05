# Schreinerei Frank – Projekt-Stellschrauben

Diese Datei beschreibt **jeden** zentralen, projektbezogenen Wert an einem Ort.
Ziel: projektbezogene Namen/Farben/Pfade **einmal** pflegen – nicht in jeder
Datei suchen müssen.

---

## 1. Zentraler `config`-Block → `data/site.json`

**Der top-level `config`-Block in `data/site.json` ist die EINZIGE Quelle für
alle projektbezogenen Werte** (Firmenname, Domain, E-Mail, Telefon sowie die
Build-Werte Theme-Farbe, Asset-Basis, Admin-Pfad). Er steht bewusst **ganz
oben** in der Datei:

```json
{
    "config": {
        "name":        "Schreinerei Frank - Meisterbetrieb",
        "domain":      "https://www.schreinerei-frank.de",
        "email":       "info@schreinerei-frank.de",
        "phone":       "+49 8624 1260",
        "themeColor":  "#3D6490",   // Browser-Tab-Farbe (<meta theme-color>)
        "assetsBase":  "/assets",   // Basis aller CSS/JS/Bilder-Pfade
        "adminPath":   "/frank-adm" // URL-Pfad des Admin-Backends
    },
    "contact": { … }
}
```

**Wie es zusammenfließt:**

- `php/config.php` liest alle Werte via `config_site_value('config.NAME', …)`
  aus diesem Block und liefert sie unter `$s['config']['project']`.
- `php/data.php` spiegelt zusätzlich `name`/`phone`/`email` in `business.*`
  und `domain` in `meta.siteUrl`, damit **alle bestehenden Aufrufe**
  (SEO, Footer, Header, JSON-LD) automatisch dieselbe Quelle nutzen.
- Abruf im Code: `site_config($s, 'project.name', 'Fallback')` bzw.
  `site_asset($s, 'pfad')`.

**Vorteil:** Es gibt **genau eine** Pflegestelle. `business.name` etc. sind
nur noch Laufzeit-Spiegel des `config`-Blocks – wer sie in site.json ändert,
überschreibt die Quelle für diesen Lauf nicht nachhaltig (sie werden aus
`config` neu gespiegelt).

**Platzhalter `{config.X}` in site.json:** Um Duplikate zu vermeiden, verweist
site.json an mehreren Stellen auf den config-Block, z. B.
`"business.email": "{config.email}"`, `"meta.title": "{config.name} · …"`,
Kontakt-Blöcke im Impressum/Datenschutz. `php/data.php` (`site_resolve_config`)
löst alle `{config.KEY}`-Platzhalter **zur Laufzeit** auf, sodass im HTML die
fertigen Werte stehen. Der Admin zeigt diese Platzhalter unverändert an
(rohe Strings) – sie werden erst beim Rendern ersetzt.

### Nicht im Admin-Panel bearbeitbar

Der `config`-Block wird **nicht** vom Admin (`/frank-adm/`) editiert. Die
Felder `business.name`, `business.email`, `business.phone` und `meta.siteUrl`
erscheinen im Admin nur noch **schreibgeschützt** (Hinweis: „Zentral in
site.json (config) gepflegt"). So kann kein Widerspruch zur Single Source of
Truth entstehen.

### Verwendungsorte dieser Werte

| Wert | Genutzt in |
|---|---|
| `name` | JSON-LD (Organization/LocalBusiness), Footer, Brand, SEO-Author |
| `domain` | JSON-LD, Canonical, Sitemap |
| `email`/`phone` | JSON-LD-Kontakt, Footer, Tel-Button |
| `themeColor` | `php/seo.php` → `<meta name="theme-color">`, **`site.webmanifest`** |
| `assetsBase` | `php/data.php` (`site_asset`), alle Asset-/Bilder-Pfade, **`site.webmanifest`**-Icons |
| `adminPath` | **Apache-`.htaccess`** (via Generator, s. u.) |
| `name` (Slug) | **`package.json`** (npm-`name`, automatisch sluggifiziert) |

---

## 2. Technische Werte, die PHP nicht „live servieren" kann

Manche Werte müssen in Dateien, die der Server/Browser/NPM direkt lesen – PHP
läuft dabei nicht. Es gibt zwei Strategien: **dynamisch ausliefern** oder
**generiert schreiben**.

```bash
# .htaccess + package.json neu generieren (beide nutzen den config-Block)
php php/config.php --gen            # alle Build-Dateien
php php/config.php --gen-htaccess   # nur die .htaccess
```

### Apache-`.htaccess` (Admin-Pfad) — generiert

Apache kann die PHP-Config nicht selbst lesen, daher wird der `adminPath`
wörtlich in die `.htaccess` geschrieben – zwischen Markern:

```apache
# >>> PROJECT ADMIN (generiert aus php/config.php) >>>
SetEnvIf Request_URI ".*" ADMIN_PATH=/frank-adm
# <<< PROJECT ADMIN (generiert aus php/config.php) <<<
```

Alle Admin-Weiterleitungen (`/admin/ → …`) und Security-Regeln beziehen sich
auf `%{ENV:ADMIN_PATH}`.

> Übrige `.htaccess`-Regeln (Caching, `.json`-Schutz, `php/*.php`-Sperrung,
> Webmanifest-/Sitemap-Routen) sind technisch und hängen nicht an der
> Projekt-Config – sie werden manuell gepflegt.

### `site.webmanifest` — dynamisch (`webmanifest.php`)

Das Manifest wird **nicht** als statische Datei gehalten, sondern zur Laufzeit
über `webmanifest.php` erzeugt. Die `.htaccess`-Route
`^site\.webmanifest$ → webmanifest.php` liefert es als
`application/manifest+json`. Name, `theme_color` (= `themeColor`) und die
Icon-/Asset-Pfade (via `assetsBase`) kommen aus `site_load()`. So bleibt das
Manifest **immer** aktuell – auch als allererster Request, ohne vorherige
Seiten-Sync.

### `package.json` — generiert

Der npm-`name` wird aus `config.name` als Slug generiert (Umlaute/Sonderzeichen
entfernt, z. B. `Schreinerei Frank – Meisterbetrieb` → `schreinerei-frank-
meisterbetrieb-root`). Dient nur dem lokalen Dev-Script (`npm run serve`).
NPM liest `package.json` **lokal vom Dateisystem** (kein HTTP-Request), daher
gibt es hier keinen dynamischen Endpunkt – die Datei muss generiert geschrieben
werden (wie die `.htaccess`).

### Automatische Synchronisierung

`php/data.php` (`site_load`) ruft beim Seitenaufruf `config_sync()` auf. Die
vergleicht den aktuellen Dateistand mit dem generierten Inhalt und schreibt
nur bei Änderung – **idempotent und billig**. Änderst du also z. B.
`themeColor` oder `adminPath` in site.json, werden `.htaccess` und
`package.json` automatisch aktuell gehalten; `site.webmanifest` ist über
`webmanifest.php` ohnehin immer aktuell. Ein manueller Generatorlauf ist nur
nötig, wenn keine Seite geladen wird.

---

## 3. Redaktionelle Inhalte → `data/site.json`

Alle **Inhalte** (Texte, Bilder, SEO/Meta, Kontakt, Slider, Galerie, FAQ,
Rezensionen). Diese Datei ist die eigentliche „Content-Single-Source" und wird
vom Admin (`/frank-adm/`) direkt gelesen/geschrieben.

**Hinweis:** Der top-level `config`-Block (siehe Abschnitt 1) ist die Quelle
für Firmenname/Domain/E-Mail/Telefon/Build-Werte und wird **nicht** vom Admin
bearbeitet. `business`/`meta` halten zusätzlich die übrigen Kontaktdaten
(Adresse, Fax, Öffnungszeiten, Geo usw.) sowie redaktionelle SEO-Texte – deren
Firmen-Kernfelder (`name`/`email`/`phone`/`siteUrl`) sind im Admin
schreibgeschützt und werden zur Laufzeit aus `config` gespiegelt.

### Nennenswerte Blöcke

| Block | Inhalt |
|---|---|
| `meta` | `siteUrl`, `title`, `description`, `og*`, `lcpImage` |
| `business` | Firmendaten, Adresse, Telefon, Fax, E-Mail, Öffnungszeiten, Geo |
| `contact` | `formAction`, `consent`, `mapNotice` |
| `nav` | Menüpunkte (Label + Anchor) |
| `hero` | Startbilder (src + alt) |
| `gallery` | Galerie-Kategorien mit Bildern + `mainAlt` |
| `services` | Leistungen-Karten (icon-Pfad) |
| `footer` / `legal` | Footer-Infos, Impressum-/Datenschutz-Texte |
| `ui` | **alle sichtbaren UI-/Beschriftungs-Texte** (Buttons, Labels, ARIA) |

---

## 4. Stellschrauben im Überblick (täglich gepflegt)

### Firmen- & Kontaktdaten
| Wozu | Wo |
|---|---|
| Firmenname, Domain, E-Mail, Telefon | `data/site.json` → `business` / `meta` (live, via config.php) |
| Adresse, Fax, Öffnungszeiten, Geo, Telefon-Link | `data/site.json` → `business` |
| E-Mail/Telefon im Footer | `data/site.json` → `business` |
| Formular-Versandziel | `data/site.json` → `contact.formAction` |

### Farben & Design
| Wozu | Wo |
|---|---|
| Primär-/Akzent-Farben (CSS-Variablen) | `src/styles/global.css` (`--primary`, `--accent` …) |
| Browser-Tab-Farbe (`theme-color`) | `php/config.php` → `themeColor` |
| Design-Grundlagen | `DESIGN.md` |

### Pfade / Struktur
| Wozu | Wo |
|---|---|
| Asset-Basis (`/assets`) | `php/config.php` → `assetsBase` |
| Admin-Pfad (`/frank-adm`) | `php/config.php` → `adminPath` (+ Generator) |
| Logo-/Icon-Dateien | `assets/site/` |
| Bilder (Content) | `assets/content/…` |

### Texte (alle sichtbaren Beschriftungen)
| Wozu | Wo |
|---|---|
| Buttons, Labels, ARIA, Formular, Dialogs | `data/site.json` → `ui` |
| Sektions-Texte, Kicker, Überschriften | `data/site.json` (jeweiliger Block) |
| Impressum/Datenschutz | `data/site.json` → `legal` |

---

## 5. Verfügbare Helfer (PHP)

In `php/data.php`:

```php
site_config($s, 'project.name', 'Fallback') // Konfig-Wert aus config.php
site_asset($s, 'content/x.jpg')             // assetsBase + Pfad, robust
site_get_str($s, 'business.phone', '')       // verschachtelter JSON-Wert
site_abs($s, '/pfad')                        // absolute URL mit meta.siteUrl
site_esc($s, $text)                          // HTML-escaped
```

---

## 6. Workflow-Zusammenfassung

1. **Firmendaten (Name/E-Mail/Telefon/Domain)** → `data/site.json` (`business`, `meta`).
2. **Technische Werte (themeColor/assetsBase/adminPath)** → `php/config.php`.
3. **`.htaccess` synchronisieren** (nach Änderung von `adminPath`) → `php php/config.php --gen-htaccess`.
4. **Texte/Bilder/SEO ändern** → `data/site.json` (oder Admin `/frank-adm/`).
5. **Design/Farben ändern** → `src/styles/global.css` (+ `config.themeColor`).

Nach jeder Konfig-Änderung: `php -l php/config.php` und Seiten-Test –
Details siehe `README.md`.
