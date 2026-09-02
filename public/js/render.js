/* =============================================================
   Schreinerei Frank – statischer Renderer
   Lädt content/site.json (-> /data/site.json) zur Laufzeit und
   befüllt die statische HTML-Seite damit. Danach wird main.js
   geladen, damit die Slider/Lightbox auf dem fertigen DOM laufen.
   ============================================================= */
(function () {
  'use strict';

  var DATA_URL = '/data/site.json';
  var html = document.documentElement;

  function getPath(obj, path) {
    return path.reduce(function (o, k) { return o == null ? undefined : o[k]; }, obj);
  }
  function setText(node, value) {
    node.textContent = value == null ? '' : String(value);
  }
  function setAttr(node, name, value) {
    if (value == null || value === '') node.removeAttribute(name);
    else node.setAttribute(name, value);
  }
  function toWebp(src) {
    return typeof src === 'string' ? src.replace(/\.(jpe?g|png)$/i, '.webp') : src;
  }

  /* ---------- JSON-LD (wie zuvor aus .eleventy.js/server.js) ---------- */
  function buildStructuredData(s) {
    var base = s.meta.siteUrl.replace(/\/$/, '');
    var abs = function (p) { return p && p.indexOf('http') === 0 ? p : base + p; };
    var b = s.business;
    return {
      '@context': 'https://schema.org',
      '@graph': [
        {
          '@type': 'Organization', '@id': base + '/#organization', name: b.name,
          url: base + '/', logo: base + '/assets/img/logo-schreinerei-frank.png',
          email: b.email, telephone: b.phoneSchema, founder: { '@type': 'Person', name: b.owner },
          address: { '@type': 'PostalAddress', streetAddress: b.street, postalCode: b.postalCode, addressLocality: b.city, addressRegion: b.region, addressCountry: b.country }
        },
        {
          '@type': 'WebSite', '@id': base + '/#website', url: base + '/', name: b.name,
          inLanguage: 'de-DE', publisher: { '@id': base + '/#organization' }
        },
        {
          '@type': ['LocalBusiness', 'Carpenter', 'HomeAndConstructionBusiness'],
          '@id': base + '/#business', name: b.name, image: abs(s.meta.ogImage), url: base + '/',
          telephone: b.phoneSchema, faxNumber: b.faxSchema, email: b.email, priceRange: b.priceRange,
          parentOrganization: { '@id': base + '/#organization' },
          address: { '@type': 'PostalAddress', streetAddress: b.street, postalCode: b.postalCode, addressLocality: b.city, addressRegion: b.region, addressCountry: b.country },
          geo: { '@type': 'GeoCoordinates', latitude: b.geo.lat, longitude: b.geo.lng },
          hasMap: b.mapUrl,
          areaServed: b.areaServed.map(function (item) { return { '@type': 'Place', name: typeof item === 'string' ? item : item.ort }; }),
          openingHoursSpecification: b.openingHoursSpec.map(function (o) {
            return { '@type': 'OpeningHoursSpecification', dayOfWeek: o.days, opens: o.opens, closes: o.closes };
          }),
          hasOfferCatalog: {
            '@type': 'OfferCatalog', name: 'Leistungen der ' + b.name,
            itemListElement: s.services.map(function (sv) {
              return { '@type': 'Offer', itemOffered: { '@type': 'Service', name: sv.title, serviceType: sv.serviceType } };
            })
          }
        },
        {
          '@type': 'WebPage', '@id': base + '/#webpage', url: base + '/', name: s.meta.title,
          isPartOf: { '@id': base + '/#website' }, about: { '@id': base + '/#business' },
          inLanguage: 'de-DE', primaryImageOfPage: abs(s.meta.ogImage)
        },
        {
          '@type': 'BreadcrumbList', '@id': base + '/#breadcrumb',
          itemListElement: [{ '@type': 'ListItem', position: 1, name: 'Start', item: base + '/' }]
        },
        {
          '@type': 'FAQPage', '@id': base + '/#faq',
          mainEntity: s.faq.map(function (f) {
            return { '@type': 'Question', name: f.q, acceptedAnswer: { '@type': 'Answer', text: f.a } };
          })
        }
      ]
    };
  }

  /* ---------- Feld-Anwendungen in einem Scope ---------- */
  function applyFields(scope, data) {
    scope.querySelectorAll('[data-bind]').forEach(function (node) {
      var path = node.getAttribute('data-bind').split('.');
      // "." = gesamtes aktuelles Objekt (Primitivlisten-Anteil)
      var val = (node.getAttribute('data-bind') === '.') ? data : getPath(data, path);
      if (node.tagName === 'INPUT' || node.tagName === 'TEXTAREA' || node.tagName === 'SELECT') {
        node.value = val == null ? '' : val;
      } else if (node.tagName === 'SPAN') {
        setText(node, val);
      } else {
        setText(node, val);
      }
    });

    scope.querySelectorAll('[data-attr-bind]').forEach(function (node) {
      var spec = node.getAttribute('data-attr-bind');
      spec.split(',').forEach(function (pair) {
        var parts = pair.split(':');
        if (parts.length < 2) return;
        var attr = parts[0].trim();
        var expr = parts.slice(1).join(':').trim();
        var val = applyExpr(data, expr);
        setAttr(node, attr, val);
      });
    });

    scope.querySelectorAll('[data-html]').forEach(function (node) {
      var val = getPath(data, node.getAttribute('data-html').split('.'));
      node.innerHTML = val == null ? '' : val;
    });
  }

  /* Expression: webp(pfad) -> konvertiert zu .webp; sonst Pfad */
  function applyExpr(data, expr) {
    var m = expr.match(/^webp\((.+)\)$/);
    if (m) {
      return toWebp(getPath(data, m[1].trim().split('.')));
    }
    return getPath(data, expr.split('.'));
  }

  /* ---------- Listen rendern ---------- */
  function renderList(root, list) {
    var template = root.querySelector('template');
    if (!template || !Array.isArray(list)) return;
    var wrap = template.parentNode;
    list.forEach(function (item) {
      var frag = template.content.cloneNode(true);
      applyFields(frag, item);
      wrap.insertBefore(frag, template);
    });
    template.remove();
  }

  /* ---------- Hero-Slider: erste Bild aktiv + Dots bauen ---------- */
  function setupHero(root) {
    var imgs = root.querySelectorAll('[data-hero-img]');
    var dots = root.querySelector('.hero-slider__dots');
    if (!imgs.length) return;
    imgs.forEach(function (img, i) {
      if (i === 0) {
        img.classList.add('is-active');
        var fi = img.querySelector('img');
        if (fi) fi.setAttribute('fetchpriority', 'high');
      }
    });
    if (dots) {
      dots.innerHTML = '';
      imgs.forEach(function (_, i) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'hero-slider__dot' + (i === 0 ? ' is-active' : '');
        b.setAttribute('data-hero-dot', String(i));
        b.setAttribute('aria-label', 'Bild ' + (i + 1) + ' von ' + imgs.length);
        dots.appendChild(b);
      });
    }
  }

  /* ---------- Galerie-Zoom-Buttons: index setzen ---------- */
  function setupGallery(root) {
    var slides = root.querySelectorAll('.gallery__slide');
    slides.forEach(function (slide, i) {
      var zoom = slide.querySelector('[data-gallery-open]');
      if (zoom) zoom.setAttribute('data-gallery-open', String(i));
    });
  }

  /* ---------- Meta-Tags ---------- */
  function applyMeta(s) {
    var setContent = function (sel, val) { var n = document.querySelector(sel); if (n) setAttr(n, 'content', val); };

    var titleNode = document.querySelector('title');
    if (titleNode) {
      var tp = titleNode.getAttribute('data-title-path');
      var title = tp ? getPath(s, tp.split('.')) : s.meta.title;
      setText(titleNode, title);
    }
    var descNode = document.querySelector('meta[name="description"]');
    if (descNode) {
      var dp = descNode.getAttribute('data-desc-path');
      setAttr(descNode, 'content', dp ? getPath(s, dp.split('.')) : s.meta.description);
    }
    setContent('meta[name="author"]', s.business.name + ', Inhaber ' + s.business.owner);
    setContent('meta[property="og:site_name"]', s.business.name);
    setContent('meta[property="og:title"]', s.meta.ogTitle);
    setContent('meta[property="og:description"]', s.meta.ogDescription);
    setContent('meta[property="og:image"]', s.meta.siteUrl + s.meta.ogImage);
    setContent('meta[property="og:image:alt"]', s.business.name);
    setContent('meta[name="twitter:title"]', s.meta.ogTitle);
    setContent('meta[name="twitter:description"]', s.meta.ogDescription);
    setContent('meta[name="twitter:image"]', s.meta.siteUrl + s.meta.ogImage);

    var preload = document.querySelector('link[data-lcp]');
    if (preload) setAttr(preload, 'href', toWebp(s.meta.lcpImage));

    var canonical = document.querySelector('link[rel="canonical"]');
    if (canonical) {
      var base = s.meta.siteUrl.replace(/\/$/, '');
      var page = document.body.getAttribute('data-page') || '';
      var path = page ? '/' + page.replace(/^\/+|\/+$/g, '') + '/' : '/';
      setAttr(canonical, 'href', base + path);
    }
  }

  /* ---------- Structured-Data einbetten ---------- */
  function applyStructuredData(s) {
    var script = document.getElementById('structured-data');
    if (script) script.textContent = JSON.stringify(buildStructuredData(s));
  }

  /* ---------- Hauptlauf ---------- */
  function fill(s) {
    applyMeta(s);
    applyStructuredData(s);

    // Kopf-Benennung (Business-Name etc.)
    applyFields(document.body, s);
    // <head>-Titel über den data-bind auf dem title-Tag wurde schon gesetzt

    // Listen rendern
    document.querySelectorAll('[data-list]').forEach(function (root) {
      var val = getPath(s, root.getAttribute('data-list').split('.'));
      if (Array.isArray(val)) renderList(root, val);
    });

    // Galerie-Kategorien für die Lightbox
    var galleryRoot = document.querySelector('[data-gallery]');
    if (galleryRoot) {
      galleryRoot.setAttribute('data-gallery-categories', JSON.stringify(s.gallery || []));
      setupGallery(galleryRoot);
    }

    // Hero-Dots + aktive Bilder
    var heroRoot = document.querySelector('[data-hero-slider]');
    if (heroRoot) setupHero(heroRoot);

    // CSS für no-JS entfernen
    html.classList.remove('no-js');
    html.classList.add('js');

    document.dispatchEvent(new CustomEvent('site:rendered', { detail: s }));
  }

  /* ---------- main.js NACH dem Rendern laden ---------- */
  function loadMain() {
    return new Promise(function (resolve, reject) {
      var existing = document.querySelector('script[data-app-main]');
      if (existing) { resolve(); return; }
      var script = document.createElement('script');
      script.src = '/js/main.js';
      script.setAttribute('data-app-main', '');
      script.onload = resolve;
      script.onerror = reject;
      document.head.appendChild(script);
    });
  }

  function run() {
    fetch(DATA_URL)
      .then(function (r) {
        if (!r.ok) throw new Error('site.json nicht gefunden (' + r.status + ')');
        return r.json();
      })
      .then(function (s) {
        fill(s);
        html.classList.remove('no-js');
        html.classList.add('js');
        return loadMain();
      })
      .catch(function (err) {
        console.error('[render] Fehler:', err);
        html.classList.add('js');
        var errBox = document.getElementById('render-error');
        if (errBox) errBox.hidden = false;
      });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
