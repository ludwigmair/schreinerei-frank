# Netlify-Setup – Schreinerei-Frank Onepager

Zwei Umgebungen über zwei Git-Branches. Der Admin (Decap CMS) ist **einer**,
arbeitet aber je nach Site in den passenden Branch – so bleiben Test- und
Live-Inhalte sauber getrennt.

| Umgebung | Branch | Domain |
|---|---|---|
| **Test** (läuft zuerst) | `staging` | `https://schreinerei-frank.typopublic.com` |
| **Live** (später) | `main` | `https://www.schreinerei-frank.de` |

> In beiden Branches ist `backend: github` mit `repo: "ludwigmair/schreinerei-frank"`
> gesetzt (`src/admin/config.yml`). Der Admin meldet sich mit **GitHub-Login**
> an – es gibt kein eigenes Passwort.

---

## Teil 1 – Test-Site (Test-Subdomain läuft zuerst)

1. **Netlify** öffnen → **Add new site → Import an existing project → GitHub**.
2. Dein Repo `ludwigmair/schreinerei-frank` autorisieren (falls Netlify noch
   keinen GitHub-Access hat).
3. **Branch auswählen**: `staging` (die zuerst laufende Test-Umgebung).
4. **Build-Einstellungen** (gelten für beide Sites gleich):
   - Build command: `npm run build`
   - Publish directory: `_site`
   - Node-Version: **20 (LTS)** unter *Deploys → Build settings*.
5. **Deploy** anstoßen → Netlify liefert dir eine zufällige `.netlify.app`-URL.
   Verifiziere dort, dass die Seite sauber rendert.
6. **Benutzerdefinierte Domain**:
   - *Sidebar → Domain management → Add custom domain* →
     `schreinerei-frank.typopublic.com`.
   - Bei **typopublic** einen **CNAME/CNAME-Flatten** anlegen: Netlify zeigt dir
     im Domain-Panel den exakten Zielwert (z. B. `deine-site.netlify.app`).

---

## Teil 2 – Admin/Login auf der Test-Site aktivieren

7. **Sidebar → Identity → Enable Identity**.
8. **Sidebar → Identity → External providers → Add provider → GitHub**:
   - GitHub **OAuth-App** unter `github.com/settings/developers` anlegen
     (New OAuth App):
     - Homepage URL: `https://schreinerei-frank.typopublic.com`
     - Authorization callback URL:
       `https://schreinerei-frank.typopublic.com/.netlify/identity/callback`
   - Die erhaltene **Client ID** + **Client Secret** in Netlify eintragen.
9. **Sidebar → Identity → Services → Git Gateway** aktivieren (bei
   *Enable* wird das Repo autorisiert – bestätigen).
10. **Redakteure**: Bei GitHub-Login ist **keine** Einladung nötig. Einladungen
    (`Identity → Invite users`) nur verwenden, wenn jemand ohne eigenen
    GitHub-Account einloggen soll.
11. **Test**: Öffne `https://schreinerei-frank.typopublic.com/admin/` →
    **„Login with GitHub"** → erster Seitenaufbau kann einige Sekunden dauern.
    Jedes Speichern committet in `staging`, Netlify baut neu, die Seite wird
    aktualisiert.

---

## Teil 3 – später: Live-Site (wenn bereit)

Wiederhole **Teil 1 + 2** für eine **zweite, separate Netlify-Site** mit:

- Branch `main`
- Domain `www.schreinerei-frank.de` (DNS beim Domain-Anbieter,
  Netlify verweist unter *Sidebar → Domain management* auf die Werte)

> **Tipp:** Für beide Sites kannst du eine **eine** *shared/server-side*
> GitHub-OAuth-App verwenden, statt pro Site eine neue anzulegen. Das ist
> etwas für Fortgeschrittene – im Zweifel einfach je Site eine OAuth-App anlegen.

---

## Stolpersteine & Hinweise

- **GitHub-OAuth-App**: muss je Site einmalig angelegt werden; nur wer eine
  *shared* App nutzt, erstellt sie genau einmal.
- **Test-Subdomain bei typopublic**: Domain **bei Netlify** hinzufügen **und**
  den **CNAME bei typopublic** anlegen. Den Zielwert zeigt Netlify im Domain-Panel.
- **`base_url`**: Auf Netlify mit Git-Gateway/GitHub-Login musst du nichts weiter
  setzen – das funktioniert automatisch.
- **Domain-Angaben in Content**: `meta.siteUrl` in `content/site.json` sowie
  `src/robots.txt`, `src/sitemap.xml`, `src/llms.txt` sind **je Branch** bereits
  auf die passende Domain gesetzt:
  - `staging` → `https://schreinerei-frank.typopublic.com`
  - `main` → `https://www.schreinerei-frank.de`
- Änderst du Inhalte, committe der Admin in den Branch, **aus dem die jeweilige
  Site deployed**. Für getrennte Test-/Live-Inhalte also in `staging` bzw.
  `main` arbeiten – niemals die Branches der Sites vertauschen.

---

## Kurz-Checkliste für eine neue Site

- [ ] Repo importiert, richtiger Branch gesetzt
- [ ] Build: `npm run build` / Publish: `_site` / Node 20
- [ ] Erste Domain: Netlify-Domain + CNAME beim Anbieter
- [ ] Identity aktiviert
- [ ] GitHub als External Provider (OAuth-App) angelegt
- [ ] Git Gateway aktiviert (Repo autorisiert)
- [ ] `/admin/` + GitHub-Login getestet
