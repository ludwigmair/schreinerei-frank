# DESIGN.md — Schreinerei Frank

Design-System des Onepagers. Umgesetzt aus **Konzept C „Meisterhand"**
(nahbar, vertrauensbildend, service-orientiert; Zielgruppe 25–60,
bewusst *nicht* nach KI-Baukasten aussehend).

Alle Werte leben als CSS Custom Properties in
[`src/styles/global.css`](src/styles/global.css) (`:root`). Diese Datei ist die
Referenz dazu.

---

## 1. Grundhaltung

| Prinzip | Umsetzung |
|---|---|
| Handwerklich, nicht „techy" | Warmes Salbeigrün + Holzocker, matte Flächen, dezente Karten-Schatten statt Effekt-Verläufen/Glas |
| Gut bedienbar (auch 55+) | Body ≥ 17 px, Buttons ≥ 54 px, Tap-Ziele ≥ 44 px, hoher Kontrast, Sticky-Telefonbutton |
| Zeitlos | Eine Sans-Familie (Fira Sans), ein Serifen-Kursiv-Akzent, Radius 6 px, kein Trend-Dekor |
| Vertrauen zuerst | Trust-Row, Meister/Region/Material, Ansprechpartner mit Foto, echte Referenzen, FAQ |

---

## 2. Farb-Tokens

| Token | Wert | Rolle |
|---|---|---|
| `--bg` | `#FFFFFF` | Seitenhintergrund |
| `--band` | `#ECF0EA` | Abschnitts-Band (jede zweite Sektion), Icon-Flächen |
| `--ink` | `#1F2A24` | Fließtext, Überschriften |
| `--muted` | `#55605A` | Zweittext, Bildunterschriften |
| `--pine` | `#2C5138` | Primäraktion (Buttons, Links, aktive Punkte) |
| `--pine-d` | `#22402C` | Hover, Footer-Hintergrund |
| `--ochre` | `#C58A3D` | Kicker-Labels, kleine Akzente, Fokusring |
| `--border` | `#D5DDD3` | Rahmen, Trennlinien |

**Kontrast:** `--ink` auf `--bg` ≈ 13:1, `--pine` auf `--bg` ≈ 8:1, `--muted` auf `--bg` ≈ 5,7:1 → alle ≥ WCAG AA.
Ocker nie für Fließtext (nur Labels/Deko).

---

## 3. Typografie

- **Familie:** `Fira Sans` (400/500/600/700) für alles.
- **Akzent:** `Spectral` *italic* – ausschließlich für den Claim „Von der Idee zum Traumhaus."
- **Fallback:** `"Avenir Next", "Segoe UI", Roboto, -apple-system, Arial, sans-serif`
- Aktuell via Google Fonts CDN geladen. **Für Produktion self-hosten** (siehe README, CWV).

### Type-Scale (`clamp()` = fluid mobil → Desktop)

| Token | Größe | Einsatz |
|---|---|---|
| `--step-4` | `clamp(2rem, 5.5vw, 2.875rem)` | H1 (nur 1×) |
| `--step-3` | `2rem` (32 px) | H2 Sektionstitel |
| `--step-2` | `1.5rem` (24 px) | frei |
| `--step-1` | `1.1875rem` (19 px) | H3 (Cards, Person) |
| `--step-0` | `1.0625rem` (17 px) | Body |
| — | `0.8125rem` (13 px) | Kicker (700, `letter-spacing:.1em`, uppercase) |

Zeilenhöhe Body 1.65, Überschriften 1.18, `letter-spacing:-.01em` auf Headings.

---

## 4. Raster & Abstände

- **Container:** `--maxw: 1180px`, Innenabstand `--pad-x: clamp(20px, 5vw, 40px)`.
- **Sektionsabstand:** `padding-block: clamp(48px, 8vw, 74px)`; `.section--band` alterniert die Fläche.
- **Abstandslogik:** 8-px-Basis (8 · 16 · 24 · 38 · 54 · 74).
- **Grids:** Cards 3-spaltig → 2 → 1; Kontakt/Über 2-spaltig → 1; Trust 4 → 2.
- **Radius:** `--radius: 6px` (Buttons, Karten, Felder, Bilder).
- **Schatten:** nur Cards, `0 1px 2px rgba(31,42,36,.04)`.
- `scroll-margin-top: 96px` auf allen `[id]` wegen Sticky-Header.

---

## 5. Komponenten

### Button `.btn`
- Höhe 54 px, `--radius`, Font 600/16 px.
- `.btn` = Pine gefüllt · `.btn--ghost` = weiß + Pine-Border · `.btn--block` = volle Breite.
- Hover → `--pine-d`; `:active` → `translateY(1px)`.

### Header `.site-header`
- `position: sticky`, halbtransparent + `backdrop-filter`.
- Ab `max-width: 860px`: Burger-Toggle (`.nav-toggle`), Nav klappt als `max-height`-Panel auf.
- `.tel-btn` immer sichtbar.

### Cards `.card`
- Weiß, 1 px Border, 24 px Padding, Icon in 44-px-Fläche (`--band`), `stroke` in `--pine`.

### Galerie `.gallery` → siehe Abschnitt 7.

### FAQ `.faq`
- Native `<details>/<summary>`, `+` / `–`-Marker via `::after`.
- JS macht daraus ein Akkordeon (max. 1 offen) – ohne JS bleiben alle einzeln aufklappbar.

### Formular `.field`
- 1-px-Border, `--radius`, Font 16 px (kein iOS-Zoom), Fokus → `--pine`-Border + Ocker-`box-shadow`.
- Honeypot `.field--hp` (off-screen). Status-Text `[data-contact-status]` mit `aria-live`.

### Footer `.site-footer`
- `--pine-d`-Fläche, dreispaltig, Text `#dce6dd`.

---

## 6. Interaktion & Barrierefreiheit

- **Fokus:** global `:focus-visible` → `box-shadow: 0 0 0 3px rgba(197,138,61,.55)`.
- **Skip-Link** `.skip-link` zu `#inhalt`.
- **Reduced Motion:** `@media (prefers-reduced-motion: reduce)` schaltet Smooth-Scroll und Transitions ab, Galerie-Autoplay bleibt aus.
- **Tastatur:** Nav, FAQ, Galerie (←/→), Formular vollständig bedienbar.
- **Landmarks:** genau ein `header/main/footer`, `nav[aria-label]`, Sektionen mit `id` + `h2`.
- **Bilder:** alle mit beschreibendem `alt`, `width`/`height` gesetzt (kein CLS).
- Ziel: Lighthouse A11y = 100, keine axe-Verstöße.

---

## 7. Galerie-Verhalten (Spezifikation)

| Aspekt | Verhalten |
|---|---|
| Richtung | Horizontaler Slider, Bewegung **von links nach rechts** |
| Sichtbare Bilder | **2** (≤ 639 px) → **3** (≥ 640 px) → **4** (≥ 1000 px); quadratisch (`aspect-ratio: 1/1`) |
| Technik | Nativer `scroll-snap-type: x mandatory` + `scroll-snap-align: start`; CSS `scroll-behavior: smooth` am Track (nicht das `behavior`-Argument von `scrollTo`, das mit `snap: mandatory` in Chromium unzuverlässig ist) |
| „Eine Ansicht" | Pfeile/Punkte scrollen um genau **eine Track-Breite** weiter; Snap rastet aufs nächste Bild |
| Bedienung | Wischen/Drag · runde Pfeil-Buttons · Punkt-Indikatoren (dynamisch: `round(scrollWidth / clientWidth)`) · Tastatur ←/→ bei fokussiertem Track |
| Autoplay | `data-autoplay="6000"` (ms). Pause bei `mouseenter`/`focusin`/`touchstart`/`pointerdown` und `visibilitychange`; eigener **Pause/Weiter-Button**. Bei `prefers-reduced-motion` **komplett aus**, Button ausgeblendet |
| Statusansage | `role="status" aria-live="polite"` → „Bildgruppe X von N (6 Bilder gesamt)" |
| Ohne JS | Track bleibt frei scroll-/wischbar; Pfeile & Punkte werden ausgeblendet (`.no-js .gallery__controls{display:none}`) |
| Bild-Ladung | Bild 1 eager, ab Bild 2 `loading="lazy"` + `decoding="async"`; Produktions-Pipeline (AVIF/WebP/JPG + `srcset`) als Kommentar im Markup |

Markup: `ul.gallery__track > li.gallery__slide > figure > img + figcaption`.
JS: [`src/scripts/main.js`](src/scripts/main.js), IIFE `gallery()`. Reines Progressive Enhancement.

---

## 8. Assets

- Bilder in `assets/img/` sind **SVG-Platzhalter** (klar beschriftet „Bildplatz").
  Vor Launch durch echte Fotos ersetzen – Dateinamen sind bereits SEO-sprechend
  (`einbaukueche-wildeiche-seeon.*`). Content-Fotos liegen in `assets/content/`,
  Logos/Icons in `assets/site/`.
- `favicon.svg` = grünes Dachdreieck-Signet. `apple-touch-icon.png` / `icon-192/512.png` /
  `og.jpg` noch anlegen (im HTML/Manifest referenziert).
