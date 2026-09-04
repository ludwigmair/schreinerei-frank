/* =============================================================
   Frank-Adm – Inhalte-Editor (kommuniziert mit der PHP-API)
   - Login/Logout über api.php
   - Lädt & speichert data/site.json über die API (sofort wirksam)
   - Bild-Upload mit WebP-Erzeugung + Bild-Bibliothek
   - Generischer Schema-Editor (deckt alle Inhalte ab)
   ============================================================= */
(function () {
  'use strict';

  var API = '/frank-adm/api.php';
  var IMG_DIR = '/assets/img/';

  var data = null;   // letzter geladener Stand
  var work = null;   // editierbare Kopie
  var CONFIG = {};   // zentrale config-Werte für die Anzeige
  var images = [];   // Bild-Bibliothek
  var uploads = {};  // path -> {blob}
  var focusTarget = null; // { path: [...] } – Ziel der Such-Sprünge

  /* ---------- Sektionen ---------- */
  var SECTIONS = [
    { key: 'hero', label: 'Hero / Slider' },
    { key: 'trust', label: 'Trust-Punkte' },
    { key: 'servicesIntro', label: 'Leistungen (Einleitung)' },
    { key: 'services', label: 'Leistungen' },
    { key: 'galleryIntro', label: 'Galerie (Einleitung)' },
    { key: 'gallery', label: 'Galerie / Projekte' },
    { key: 'about', label: 'Über uns' },
    { key: 'faqIntro', label: 'FAQ (Einleitung)' },
    { key: 'faq', label: 'FAQ' },
    { key: 'testimonialsIntro', label: 'Rezensionen (Einleitung)' },
    { key: 'testimonials', label: 'Rezensionen' },
    { key: 'contact', label: 'Kontakt' },
    { key: 'nav', label: 'Navigation' },
    { key: 'business', label: 'Betrieb / Kontaktdaten' },
    { key: 'meta', label: 'SEO / Meta' },
    { key: 'footer', label: 'Footer' },
    { key: 'legal', label: 'Impressum / Datenschutz' }
  ];

  // Pro Listen-Abschnitt: kurzer Präfix für die Headline + gewünschtes
  // "erkennendes" Feld (falls vorhanden), das zusätzlich angezeigt wird.
  var ITEM_META = {
    images:      { feature: 'src' },
    gallery:     { feature: 'category' },
    faq:         { feature: 'q' },
    trust:       { feature: 'value' },
    services:    { feature: 'title' },
    testimonials:{ feature: 'source' },
    nav:         { feature: 'label' }
  };

  function itemLabel(holderKey, item, index) {
    var meta = ITEM_META[holderKey] || {};
    var f = meta.feature;
    var v = (f && item && item[f]) ? String(item[f]) : '';
    if (!v) {
      v = String(item && (item.title || item.heading || item.name || item.alt || item.text || ''));
    }
    if (f === 'src') v = v.split('/').pop();
    if (!v) return friendly(holderKey);
    if (v.length > 60) v = v.slice(0, 60) + '…';
    return v;
  }

  var FORM_LABELS = {
    kicker: 'Kicker', heading: 'Überschrift', claim: 'Claim', lead: 'Einleitung',
    text: 'Text', title: 'Titel', description: 'Beschreibung',
    ctaPrimary: 'Button primär', ctaSecondary: 'Button sekundär', personCta: 'Button-Text',
    label: 'Beschriftung', value: 'Wert', quote: 'Zitat', source: 'Quelle',
    category: 'Kategorie', serviceType: 'Typ', icon: 'Icon (SVG-Pfad)',
    q: 'Frage', a: 'Antwort', name: 'Name', owner: 'Inhaber', street: 'Straße',
    postalCode: 'PLZ', city: 'Ort', region: 'Region', country: 'Land (ISO)',
    phone: 'Telefon (Anzeige)', phoneHref: 'Telefon (Link)', phoneSchema: 'Telefon (Schema)',
    fax: 'Telefax (Anzeige)', faxSchema: 'Telefax (Schema)', email: 'E-Mail',
    priceRange: 'Preisniveau', mapUrl: 'Karten-Link', openingHoursText: 'Öffnungszeiten',
    siteUrl: 'Basis-URL', ogTitle: 'OG-Titel', ogDescription: 'OG-Beschreibung',
    ogImage: 'OG-Bild', lcpImage: 'LCP-Bild',
    intro: 'Intro', personRole: 'Rolle', personImage: 'Foto', personImageAlt: 'Foto Alt-Text',
    mainImage: 'Hauptbild', mainAlt: 'Alt-Text Hauptbild', src: 'Bild', alt: 'Alt-Text',
    href: 'Ziel (#anker)', url: 'URL', impressumHref: 'Impressum-Link', datenschutzHref: 'Datenschutz-Link',
    formAction: 'Formular-Ziel', consent: 'Einwilligungshinweis', mapNotice: 'Karten-Hinweis',
    days: 'Tage', opens: 'Öffnet', closes: 'Schließt', lat: 'Breite (lat)', lng: 'Länge (lng)',
    placeId: 'Google-Platz-ID', apiKey: 'API-Key (optional)', rating: 'Bewertung', reviewCount: 'Anzahl',
    editCount: '', companyName: 'Firmenname', ownerName: 'Inhaber', address: 'Anschrift'
  };

  // Felder, die aus dem zentralen "config"-Block in site.json kommen und
  // deshalb NICHT im Admin bearbeitet werden dürfen (kein Widerspruch zur
  // Single Source of Truth). Siehe PROJECT.md.
  var currentSection = '';
  function friendly(name) {
    if (FORM_LABELS[name]) return FORM_LABELS[name];
    return name.replace(/([a-z])([A-Z])/g, '$1 $2').replace(/[_-]+/g, ' ').trim().replace(/^./, function (c) { return c.toUpperCase(); });
  }
  function isObj(v) { return v && typeof v === 'object' && !Array.isArray(v); }
  function isImagePath(v) {
    if (typeof v !== 'string') return false;
    return /\.(jpe?g|png|webp|gif|svg|avif)(\?.*)?$/i.test(v) || v.indexOf('data:image/') === 0;
  }
  function isImageKey(k) {
    return ['src', 'mainImage', 'ogImage', 'lcpImage', 'personImage', 'image', 'logo', 'favicon'].indexOf(k) > -1;
  }

  /* ---------- DOM-Helfer ---------- */
  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }
  function $(sel) { return document.querySelector(sel); }
  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }
  function toast(msg, type) {
    var t = $('#toast');
    t.textContent = msg;
    t.className = 'toast ' + (type || '');
    t.hidden = false;
    clearTimeout(t._h);
    t._h = setTimeout(function () { t.hidden = true; }, 2600);
  }
  function setStatus(msg) { $('#status').textContent = msg; }

  /* ---------- Drag&Drop-Popup (modulweit, robust per Pointer) ---------- */
  var dragPopup = null;
  function ensureDragPopup() {
    if (!dragPopup) {
      dragPopup = el('div', 'drag-popup');
      dragPopup.hidden = true;
      document.body.appendChild(dragPopup);
    }
    return dragPopup;
  }
  function showDragPopup(text, x, y) {
    var p = ensureDragPopup();
    p.textContent = text;
    p.hidden = false;
    p.style.left = (x + 14) + 'px';
    p.style.top = (y + 16) + 'px';
  }
  function hideDragPopup() {
    if (dragPopup) dragPopup.hidden = true;
  }

  /* Aktiver Drag-Zustand: einmaliger modulweiter Abruch-Handler (verhindert, dass
     das Positions-Popup beim Verlieren des Zeigers liegen bleibt). */
  var activeDrag = null;
  document.addEventListener('pointerup', function () { abortDrag(); });
  document.addEventListener('pointercancel', function () { abortDrag(); });
  function abortDrag() {
    if (activeDrag) {
      var d = activeDrag;
      activeDrag = null;
      d.abort();
      hideDragPopup();
    }
  }

  /* ---------- API ---------- */
  function apiGet(action) {
    return fetch(API + '?action=' + action, { credentials: 'same-origin' }).then(r => r.json());
  }
  function apiPost(action, payload) {
    return fetch(API + '?action=' + action, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(r => r.json());
  }

  /* ---------- Pfad-Utilities (für Schema-Editor) ---------- */
  function getPath(obj, path) { return path.reduce(function (o, k) { return o == null ? undefined : o[k]; }, obj); }
  function setPath(obj, path, val) {
    var last = path[path.length - 1];
    var o = path.slice(0, -1).reduce(function (o, k) { return (o[k] = o[k] || {}); }, obj);
    o[last] = val;
  }

  /* ---------- Feld-Renderer ---------- */
  function makeImageField(holder, key, value) {
    var row = el('div', 'img-row');
    var img = el('img', 'preview');
    img.alt = '';
    img.src = previewSrc(value);

    var inp = el('input'); inp.type = 'text'; inp.value = value;
    inp.addEventListener('input', function () {
      holder[key] = inp.value;
      img.src = previewSrc(inp.value);
    });

    var file = el('input'); file.type = 'file'; file.accept = 'image/*'; file.hidden = true;
    file.addEventListener('change', function () {
      if (!file.files || !file.files[0]) return;
      var f = file.files[0];
      var reader = new FileReader();
      reader.onload = function () {
        var tmp = '/pending-' + Date.now() + '.' + (f.name.split('.').pop() || 'jpg');
        uploads[tmp] = { blob: f, preview: reader.result };
        holder[key] = tmp;
        inp.value = tmp;
        img.src = reader.result;
      };
      reader.readAsDataURL(f);
    });

    var btn = el('button', 'upload-btn', '⬆ Hochladen');
    btn.type = 'button';
    btn.addEventListener('click', function () { file.click(); });

    // Bild-Bibliothek anbieten
    var libBtn = el('button', 'upload-btn', 'Auswählen…');
    libBtn.type = 'button';
    libBtn.addEventListener('click', function () { openImageLibrary(function (path) { holder[key] = path; inp.value = path; img.src = previewSrc(path); }); });

    row.appendChild(img);
    row.appendChild(inp);
    row.appendChild(btn);
    row.appendChild(libBtn);
    row.appendChild(file);
    return row;
  }

  function previewSrc(v) {
    if (uploads[v]) return uploads[v].preview;
    return v && v.indexOf('data:') === 0 ? v : v;
  }

  function configVars(value) {
    if (typeof value !== 'string') return [];
    var m = value.match(/\{config\.([a-zA-Z0-9_]+)\}/g) || [];
    return m.map(function (s) { return 'config.' + s.replace(/\}|\{/g, '').replace('config.', ''); });
  }

  function pathIsTarget(path) {
    if (!focusTarget) return false;
    var t = focusTarget.path || [];
    if (t.length !== path.length) return false;
    for (var i = 0; i < t.length; i++) if (String(t[i]) !== String(path[i])) return false;
    return true;
  }

  function isPrefixPath(prefix, path) {
    if (!prefix || prefix.length > path.length) return false;
    for (var i = 0; i < prefix.length; i++) if (String(prefix[i]) !== String(path[i])) return false;
    return true;
  }

  function resolveValue(obj, path) {
    return path.reduce(function (o, k) { return o == null ? undefined : o[k]; }, obj);
  }

  function searchLabel(holderKey, path) {
    var val = resolveValue(work, path);
    if (path.length === 1 && SECTIONS.some(function (s) { return s.key === path[0]; })) {
      var sec = SECTIONS.find(function (s) { return s.key === path[0]; });
      return sec ? sec.label : friendly(holderKey);
    }
    return friendly(holderKey);
  }

  function makeField(holder, key, value, path) {
    var vars = configVars(value);
    if (vars.length) {
      // Config-gebundene Felder: keine Eingabe, nur Anzeige. Der Wert stammt
      // zentral aus dem config-Block - NUR als Text dargestellt, niemals editierbar.
      var rw = el('div', 'field');
      if (path) rw.setAttribute('data-path', path.join('.'));
      rw.appendChild(el('label', null, friendly(key)));
      var resolved = String(value).replace(/\{config\.([a-zA-Z0-9_]+)\}/g, function (m, k) {
        return (CONFIG[k] != null ? CONFIG[k] : m);
      });
      rw.appendChild(el('div', 'config-value', resolved));
      var refs = vars.map(function (v) { return v.replace(/^config\./, '{config.').replace(/$/, '}'); });
      var b = el('span', 'config-hint');
      b.textContent = 'config wert · ' + refs.join(' · ');
      rw.classList.add('with-config');
      rw.appendChild(b);
      return rw;
    }
    var wrap = el('div', 'field');
    if (path) wrap.setAttribute('data-path', path.join('.'));
    if (path && pathIsTarget(path)) wrap.classList.add('search-focus');
    wrap.appendChild(el('label', null, friendly(key)));
    var type = typeof value;
    if (type === 'boolean') {
      var cb = el('input'); cb.type = 'checkbox'; cb.checked = !!value;
      cb.addEventListener('change', function () { holder[key] = cb.checked; });
      wrap.appendChild(cb);
    } else if (type === 'number') {
      var num = el('input'); num.type = 'number'; num.value = value;
      num.addEventListener('input', function () { holder[key] = parseFloat(num.value) || 0; });
      wrap.appendChild(num);
    } else if ((isImagePath(value) || isImageKey(key)) && key !== 'apiKey') {
      wrap.appendChild(makeImageField(holder, key, value));
    } else {
      var isLong = ['text', 'lead', 'quote', 'a', 'consent', 'mapNotice', 'intro'].indexOf(key) > -1 ||
        (typeof value === 'string' && value.length > 120);
      if (isLong) {
        var ta = el('textarea'); ta.value = value;
        ta.addEventListener('input', function () { holder[key] = ta.value; });
        wrap.appendChild(ta);
      } else {
        var inp = el('input'); inp.type = 'text'; inp.value = value;
        inp.addEventListener('input', function () { holder[key] = inp.value; });
        wrap.appendChild(inp);
      }
    }
    return wrap;
  }

  function makePrimArrayField(holder, key, arr) {
    var wrap = el('div', 'field');
    wrap.appendChild(el('label', null, friendly(key)));
    var inp = el('input'); inp.type = 'text'; inp.value = arr.join(', ');
    inp.addEventListener('input', function () {
      holder[key] = inp.value.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
    });
    wrap.appendChild(inp);
    wrap.appendChild(el('div', 'hint', 'Mit Komma getrennt.'));
    return wrap;
  }

  function renderInto(container, holder, value, holderKey, path) {
    if (Array.isArray(value)) {
      var allPrim = value.every(function (v) { return !isObj(v) && !Array.isArray(v); });
      if (allPrim) { container.appendChild(makePrimArrayField(holder, holderKey, value)); return; }

      var listWrap = el('div');
      listWrap.style.margin = '8px 0';
      var addBtn = el('button', 'btn secondary', '+ Eintrag hinzufügen');
      addBtn.type = 'button';
      addBtn.style.margin = '8px 0 4px';

      function reorderArray(from, to) {
        var arr = holder[holderKey];
        if (from === to) return;
        var item = arr.splice(from, 1)[0];
        arr.splice(to, 0, item);
      }

      function reorderListUI() {
        var boxes = listWrap.querySelectorAll('.list-item');
        boxes.forEach(function (b) { b.parentNode.removeChild(b); });
        value.forEach(function (it, i) { renderItem(it, i); });
      }

      /* ----- Drag&Drop-Helfer (pro Liste; nutzen modulweites Popup) ----- */
      var dragState = null;
      function dropPosAt(clientY) {
        var bs = listWrap.querySelectorAll('.list-item');
        for (var i = 0; i < bs.length; i++) {
          var r = bs[i].getBoundingClientRect();
          if (clientY < r.top + r.height / 2) return i;
        }
        return bs.length;
      }
      function dragHintText(pos) {
        var bs = listWrap.querySelectorAll('.list-item');
        var n = bs.length;
        if (n === 0) return '';
        if (pos <= 0) return 'An den Anfang · vor ' + itemLabel(holderKey, value[0], 0);
        if (pos >= n) return 'Ans Ende · nach ' + itemLabel(holderKey, value[n - 1], n - 1);
        return 'nach ' + itemLabel(holderKey, value[pos - 1], pos - 1) + ' · vor ' + itemLabel(holderKey, value[pos], pos);
      }
      function hideIndicator() {
        var ind = listWrap.querySelector('.drag-indicator');
        if (ind) ind.parentNode.removeChild(ind);
        listWrap.querySelectorAll('.list-item').forEach(function (b) { b.classList.remove('drop-target'); });
        hideDragPopup();
      }
      function showIndicator(pos, x, y) {
        hideIndicator();
        var bs = listWrap.querySelectorAll('.list-item');
        var marker = el('div', 'drag-indicator');
        if (bs.length === 0 || pos <= 0) listWrap.insertBefore(marker, listWrap.firstChild);
        else if (pos >= bs.length) listWrap.appendChild(marker);
        else listWrap.insertBefore(marker, bs[pos]);
        showDragPopup(dragHintText(pos), x, y);
      }

      function renderItem(itemObj, index) {
        var box = el('div', 'list-item');
        var isActive = (itemObj.active !== false);
        if (!isActive) box.classList.add('is-inactive');

        var head = el('div', 'list-item-head');

        var sortGroup = el('div', 'sort-grp');
        var up = el('button', 'arrow-btn', '↑');
        up.type = 'button';
        up.title = 'Nach oben verschieben';
        up.disabled = index === 0;
        up.addEventListener('click', function () {
          var to = index - 1;
          reorderArray(index, to);
          reorderListUI();
          toast(itemLabel(holderKey, itemObj, to) + ' → Position ' + (to + 1), 'ok');
        });
        sortGroup.appendChild(up);
        var down = el('button', 'arrow-btn', '↓');
        down.type = 'button';
        down.title = 'Nach unten verschieben';
        down.disabled = index === value.length - 1;
        down.addEventListener('click', function () {
          var to = index + 1;
          reorderArray(index, to);
          reorderListUI();
          toast(itemLabel(holderKey, itemObj, to) + ' → Position ' + (to + 1), 'ok');
        });
        sortGroup.appendChild(down);
        head.appendChild(sortGroup);

        // Drag&Drop: pointer-basiert am Griff (robust; live-Positions-Popup)
        var grip = el('span', 'grip', '⠿');
        grip.title = 'Ziehen zum Umsortieren';
        grip.setAttribute('role', 'button');
        grip.setAttribute('aria-label', 'Eintrag per Drag&Drop verschieben');
        function endDrag(commit, e) {
          if (!dragState) return;
          var from = dragState.from;
          var pos = commit ? dropPosAt(e.clientY) : from;
          dragState = null;
          activeDrag = null;
          hideIndicator();
          box.classList.remove('dragging');
          if (commit) {
            var to = pos;
            if (to > from) to = to - 1;
            reorderArray(from, to);
            reorderListUI();
            toast(itemLabel(holderKey, itemObj, to) + ' → Position ' + (to + 1), 'ok');
          }
        }
        grip.addEventListener('pointerdown', function (e) {
          e.preventDefault();
          dragState = { from: holder[holderKey].indexOf(itemObj) };
          box.classList.add('dragging');
          activeDrag = { abort: function () { endDrag(false); } };
          grip.setPointerCapture(e.pointerId);
        });
        grip.addEventListener('pointermove', function (e) {
          if (!dragState) return;
          e.preventDefault();
          showIndicator(dropPosAt(e.clientY), e.clientX, e.clientY);
        });
        grip.addEventListener('pointerup', function (e) { endDrag(true, e); });
        grip.addEventListener('pointercancel', function () { endDrag(false); });
        head.appendChild(grip);

        // Kleines Thumbnail links neben der Headline (Galerie/Slider)
        var thumbSrc = null;
        if (typeof itemObj.src === 'string' && isImagePath(itemObj.src)) thumbSrc = itemObj.src;
        else if (typeof itemObj.mainImage === 'string' && isImagePath(itemObj.mainImage)) thumbSrc = itemObj.mainImage;
        if (thumbSrc) {
          var thumb = el('img', 'item-thumb');
          thumb.src = thumbSrc;
          thumb.alt = '';
          thumb.loading = 'lazy';
          head.appendChild(thumb);
        }

        var toggler = el('button', 'title toggle-title');
        toggler.type = 'button';
        toggler.title = 'Eintrag auf-/zuklappen';
        head.appendChild(toggler);

        var titleSpan = el('span', null, itemLabel(holderKey, itemObj, index));
        toggler.appendChild(titleSpan);

        var tog = el('button', 'toggle', isActive ? 'Aktiv' : 'Inaktiv');
        tog.type = 'button';
        if (!isActive) tog.classList.add('inactive');
        tog.title = 'Eintrag aktivieren/deaktivieren';
        tog.addEventListener('click', function () {
          itemObj.active = !(itemObj.active !== false);
          var on = itemObj.active !== false;
          box.classList.toggle('is-inactive', !on);
          tog.classList.toggle('inactive', !on);
          tog.textContent = on ? 'Aktiv' : 'Inaktiv';
        });
        head.appendChild(tog);

        var del = el('button', 'icon-btn', '✕');
        del.title = 'Eintrag entfernen';
        del.addEventListener('click', function () {
          var arr = holder[holderKey];
          var i = arr.indexOf(itemObj);
          if (i > -1) arr.splice(i, 1);
          listWrap.removeChild(box);
        });
        head.appendChild(del);
        box.appendChild(head);

        var body = el('div', 'list-item-body');
        if (isObj(itemObj)) {
          Object.keys(itemObj).forEach(function (k) { renderInto(body, itemObj, itemObj[k], k, (path || []).concat(holderKey, index)); });
        }
        box.appendChild(body);

        // Auf-/Zuklappen des Eintrags über den Titel
        var itemPath = (path || []).concat(holderKey, index);
        var collapsed = !(focusTarget && focusTarget.path && isPrefixPath(focusTarget.path, itemPath));
        function applyCollapse() {
          body.hidden = collapsed;
          toggler.classList.toggle('collapsed', collapsed);
          titleSpan.textContent = (collapsed ? '▸ ' : '▾ ') + itemLabel(holderKey, itemObj, index);
        }
        toggler.addEventListener('click', function () {
          collapsed = !collapsed;
          applyCollapse();
        });
        applyCollapse();

        listWrap.insertBefore(box, addBtn);
      }

      listWrap.appendChild(addBtn);
      value.forEach(function (it, i) { renderItem(it, i); });
      addBtn.addEventListener('click', function () {
        var schema = value.length > 0 ? value[0] : {};
        var blank = {};
        Object.keys(schema).forEach(function (k) {
          var v = schema[k];
          if (isObj(v)) blank[k] = {};
          else if (Array.isArray(v)) blank[k] = [];
          else if (typeof v === 'boolean') blank[k] = false;
          else if (typeof v === 'number') blank[k] = 0;
          else blank[k] = '';
        });
        holder[holderKey].push(blank);
        renderItem(blank, value.length - 1);
      });
      container.appendChild(listWrap);
      return;
    }

    if (isObj(value)) {
      var sub = el('div', 'sub-card');
      sub.appendChild(el('h4', null, friendly(holderKey)));
      Object.keys(value).forEach(function (k) { renderInto(sub, value, value[k], k, (path || []).concat(holderKey)); });
      container.appendChild(sub);
      return;
    }

    container.appendChild(makeField(holder, holderKey, value, path));
  }

  /* ---------- Sektionen renderen ---------- */
  function activeSection() { return document.querySelector('#nav-cards button.active'); }
  function showSection(key) {
    currentSection = key;
    var root = document.getElementById('form-root');
    var card = el('div', 'card');
    var sec = SECTIONS.find(function (s) { return s.key === key; });
    card.appendChild(el('h3', null, sec ? sec.label : friendly(key)));
    if (!(key in work)) {
      card.appendChild(el('p', 'hint', 'Dieser Abschnitt ist noch nicht vorhanden.'));
    } else {
      renderInto(card, work, work[key], key, [key]);
    }
    root.innerHTML = '';
    root.classList.remove('load-screen');
    root.appendChild(card);
    setStatus('Abschnitt geöffnet');

    // Beim Such-Sprung zum Zielfeld scrollen + hervorheben
    if (focusTarget && focusTarget.path && focusTarget.path[0] === key) {
      var t = document.querySelector('[data-path="' + focusTarget.path.join('.') + '"]');
      if (t) {
        t.scrollIntoView({ block: 'center', behavior: 'smooth' });
        t.classList.add('search-focus');
        setTimeout(function () { t.classList.remove('search-focus'); }, 2400);
      }
    }
  }

  /* ---------- Volltextsuche ---------- */
  function collectSearchables(holder, path, out) {
    if (isObj(holder)) {
      Object.keys(holder).forEach(function (k) {
        collectSearchables(holder[k], (path || []).concat(k), out);
      });
      return;
    }
    if (Array.isArray(holder)) {
      holder.forEach(function (it, i) {
        if (it != null && typeof it === 'object') collectSearchables(it, (path || []).concat(i), out);
      });
      return;
    }
    if (typeof holder === 'string' || typeof holder === 'number') {
      var label = friendly(path[path.length - 1]);
      var secKey = path[0];
      var sec = SECTIONS.find(function (s) { return s.key === secKey; });
      out.push({ path: path.slice(), label: label, section: sec ? sec.label : friendly(secKey), value: String(holder) });
    }
  }

  function runSearch(query) {
    var list = document.getElementById('search-results');
    list.innerHTML = '';
    list.hidden = true;
    if (!query) return;
    var q = query.toLowerCase();
    var hits = [];
    collectSearchables(work, [], hits);
    var text = hits.filter(function (h) {
      return h.value.toLowerCase().indexOf(q) > -1 || h.label.toLowerCase().indexOf(q) > -1;
    });
    var uid = 0;
    text.slice(0, 30).forEach(function (h) {
      var li = el('li');
      li.setAttribute('data-hpath', h.path.join('.'));
      var top = el('span', 'sr-top', h.section + ' › ' + h.label);
      var bot = el('span', 'sr-val', h.value.length > 60 ? h.value.slice(0, 60) + '…' : h.value);
      li.appendChild(top);
      li.appendChild(bot);
      li.addEventListener('click', function () { jumpTo(h.path); });
      list.appendChild(li);
    });
    if (text.length === 0) {
      var none = el('li', 'sr-none', 'Keine Treffer');
      list.appendChild(none);
    }
    list.hidden = false;
    void uid;
  }

  function jumpTo(path) {
    focusTarget = { path: path };
    document.getElementById('search-input').value = '';
    document.getElementById('search-results').hidden = true;
    var navBtns = document.querySelectorAll('#nav-cards button');
    navBtns.forEach(function (b) { b.classList.remove('active'); });
    var targets = Array.prototype.slice.call(navBtns).filter(function (b) { return b.textContent.trim() === sectionLabel(path[0]); });
    if (targets[0]) targets[0].classList.add('active');
    showSection(path[0]);
  }
  function sectionLabel(key) {
    var sec = SECTIONS.find(function (s) { return s.key === key; });
    return sec ? sec.label : friendly(key);
  }

  function buildNav() {
    var nav = document.getElementById('nav-cards');
    nav.innerHTML = '';
    SECTIONS.forEach(function (s) {
      var b = el('button', null, s.label);
      b.addEventListener('click', function () {
        nav.querySelectorAll('button.active').forEach(function (x) { x.classList.remove('active'); });
        b.classList.add('active');
        showSection(s.key);
      });
      nav.appendChild(b);
    });
    var first = SECTIONS[0];
    nav.querySelectorAll('button').forEach(function (b, i) {
      if (i === 0) b.classList.add('active');
    });
    showSection(first.key);
  }

  /* ---------- Bild-Bibliothek ---------- */
  function openImageLibrary(cb) {
    var overlay = el('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(15,20,17,.6);z-index:300;display:flex;align-items:center;justify-content:center;padding:20px;';
    var box = el('div', 'card');
    box.style.cssText = 'max-width:720px;width:100%;max-height:80vh;overflow:auto;';
    box.appendChild(el('h3', null, 'Bild auswählen'));

    var grid = el('div', 'img-library');
    images.forEach(function (im) {
      var imEl = el('img', 'thumb');
      imEl.src = im.path;
      imEl.alt = '';
      imEl.addEventListener('click', function () { cb(im.path); document.body.removeChild(overlay); });
      grid.appendChild(imEl);
    });
    box.appendChild(grid);

    var close = el('button', 'btn secondary', 'Abbrechen');
    close.type = 'button';
    close.addEventListener('click', function () { document.body.removeChild(overlay); });
    box.appendChild(close);

    overlay.appendChild(box);
    overlay.addEventListener('click', function (e) { if (e.target === overlay) document.body.removeChild(overlay); });
    document.body.appendChild(overlay);
  }

  /* ---------- Hochladen ausstehender Bilder ---------- */
  function uploadPending(work) {
    var promises = [];
    Object.keys(uploads).forEach(function (tmp) {
      var up = uploads[tmp];
      if (!up.blob) return;
      var fd = new FormData();
      fd.append('file', up.blob, up.blob.name);
      var p = fetch(API + '?action=upload', { method: 'POST', credentials: 'same-origin', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.ok) {
            replacePaths(work, tmp, res.path);
            delete uploads[tmp];
          } else {
            throw new Error(res.error || 'Upload fehlgeschlagen');
          }
        });
      promises.push(p);
    });
    return Promise.all(promises);
  }

  function replacePaths(obj, from, to) {
    Object.keys(obj).forEach(function (k) {
      var v = obj[k];
      if (typeof v === 'string' && v === from) obj[k] = to;
      else if (isObj(v)) replacePaths(v, from, to);
      else if (Array.isArray(v)) v.forEach(function (item) { if (isObj(item)) replacePaths(item, from, to); });
    });
  }

  /* ---------- Speichern ---------- */
  function save() {
    var btn = $('#save-btn');
    btn.disabled = true;
    setStatus('Speichere …');
    uploadPending(work).then(function () {
      return apiPost('save', { content: work });
    }).then(function (res) {
      btn.disabled = false;
      if (res.ok) {
        data = JSON.parse(JSON.stringify(work));
        toast('Gespeichert – Inhalte sind jetzt live.', 'ok');
        setStatus('Gespeichert ' + new Date().toLocaleTimeString('de-DE'));
      } else {
        toast('Fehler beim Speichern: ' + res.error, 'err');
        setStatus('Fehler beim Speichern');
      }
    }).catch(function (err) {
      btn.disabled = false;
      toast('Fehler: ' + err.message, 'err');
      setStatus('Fehler');
    });
  }

  /* ---------- Login ---------- */
  function login() {
    var user = ($('#user').value || '').trim();
    var pass = $('#pw').value;
    apiPost('login', { user: user, password: pass }).then(function (res) {
      if (res.ok) {
        $('#login-err').hidden = true;
        document.getElementById('login-view').hidden = true;
        document.getElementById('editor-view').hidden = false;
        $('#pw').value = '';
        loadAll();
      } else {
        $('#login-err').hidden = false;
        $('#login-err').textContent = res.error;
      }
    });
  }

  function loadAll() {
    apiGet('content').then(function (res) {
      if (!res.ok) throw new Error(res.error);
      data = res.content;
      work = JSON.parse(JSON.stringify(data));
      CONFIG = (res && res.config) || {};
      return apiGet('images');
    }).then(function (res) {
      images = res.ok ? res.images : [];
      buildNav();
    }).catch(function (err) {
      setStatus('Laden fehlgeschlagen');
      toast(err.message, 'err');
    });
  }

  function wire() {
    document.getElementById('login-btn').addEventListener('click', login);
    ['user', 'pw'].forEach(function (id) {
      document.getElementById(id).addEventListener('keydown', function (e) { if (e.key === 'Enter') login(); });
    });
    document.getElementById('logout-btn').addEventListener('click', function () {
      apiPost('logout', {}).then(function () {
        document.getElementById('editor-view').hidden = true;
        document.getElementById('login-view').hidden = false;
        $('#pw').value = '';
      });
    });
    document.getElementById('reload-btn').addEventListener('click', loadAll);
    document.getElementById('save-btn').addEventListener('click', save);

    var searchInp = document.getElementById('search-input');
    searchInp.addEventListener('input', function () { runSearch(searchInp.value.trim()); });
    searchInp.addEventListener('focus', function () { if (searchInp.value.trim()) runSearch(searchInp.value.trim()); });
    searchInp.addEventListener('keydown', function (e) {
      var list = document.getElementById('search-results');
      if (e.key === 'Escape') { list.hidden = true; searchInp.blur(); return; }
      if (e.key === 'Enter') {
        var first = list.querySelector('li[data-hpath]');
        if (first) jumpTo(first.getAttribute('data-hpath').split('.'));
      }
    });
    document.addEventListener('click', function (e) {
      var w = document.querySelector('.search-wrap');
      if (w && !w.contains(e.target)) document.getElementById('search-results').hidden = true;
    });

    if (window.SF_ADMIN && window.SF_ADMIN.loggedIn) {
      document.getElementById('login-view').hidden = true;
      document.getElementById('editor-view').hidden = false;
      loadAll();
    }
  }

  wire();
})();
