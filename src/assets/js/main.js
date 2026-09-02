/* =============================================================
   Schreinerei Frank – Onepager · Progressive Enhancement
   Nichts hier ist zum Anzeigen der Inhalte nötig:
   Galerie scrollt/snappt nativ, FAQ ist <details>, Formular postet normal.
   ============================================================= */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* -------- Mobile-Navigation -------- */
  (function nav() {
    var toggle = document.querySelector('.nav-toggle');
    var menu = document.getElementById('primary-nav');
    if (!toggle || !menu) return;

    function close() {
      menu.removeAttribute('data-open');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'Menü öffnen');
    }
    function open() {
      menu.setAttribute('data-open', '');
      toggle.setAttribute('aria-expanded', 'true');
      toggle.setAttribute('aria-label', 'Menü schließen');
    }
    toggle.addEventListener('click', function () {
      menu.hasAttribute('data-open') ? close() : open();
    });
    menu.addEventListener('click', function (e) {
      if (e.target.closest('a')) close();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') close();
    });
  })();

  /* -------- FAQ: nur eine Antwort gleichzeitig offen -------- */
  (function faq() {
    var box = document.querySelector('[data-faq]');
    if (!box) return;
    var items = box.querySelectorAll('details');
    items.forEach(function (d) {
      d.addEventListener('toggle', function () {
        if (!d.open) return;
        items.forEach(function (o) { if (o !== d) o.open = false; });
      });
    });
  })();

  /* -------- Galerie-Slider --------
     Modell: eine "Ansicht" = eine Track-Breite. Pfeile/Punkte scrollen um
     genau eine Ansicht weiter; die native scroll-snap-Ausrichtung rastet
     auf das nächste Bild ein. Kommt ohne Pro-Bild-Rechnung aus. */
  (function gallery() {
    var root = document.querySelector('[data-gallery]');
    if (!root) return;

    var track = root.querySelector('.gallery__track');
    var prevBtn = root.querySelector('[data-gallery-prev]');
    var nextBtn = root.querySelector('[data-gallery-next]');
    var pauseBtn = root.querySelector('[data-gallery-pause]');
    var dotsBox = root.querySelector('[data-gallery-dots]');
    var live = root.querySelector('[data-gallery-live]');
    var slideCount = track.children.length;
    var interval = parseInt(root.getAttribute('data-autoplay'), 10) || 0;

    var timer = null;
    var paused = reduceMotion || !interval;

    function pageCount() {
      return Math.max(1, Math.round(track.scrollWidth / Math.max(1, track.clientWidth)));
    }
    function pageIndex() {
      var maxLeft = track.scrollWidth - track.clientWidth;
      if (maxLeft <= 1) return 0;
      if (track.scrollLeft >= maxLeft - 1) return pageCount() - 1;
      return Math.round(track.scrollLeft / track.clientWidth);
    }
    // Kein "behavior"-Key: die Animation steuert CSS scroll-behavior am Track
    // (zuverlässiger im Zusammenspiel mit scroll-snap: mandatory).
    function goTo(i) {
      var n = pageCount();
      i = Math.max(0, Math.min(n - 1, i));
      track.scrollTo({ left: Math.min(i * track.clientWidth, track.scrollWidth - track.clientWidth) });
    }

    function buildDots() {
      var n = pageCount();
      dotsBox.innerHTML = '';
      for (var i = 0; i < n; i++) {
        var li = document.createElement('li');
        var b = document.createElement('button');
        b.type = 'button';
        b.setAttribute('aria-label', 'Bildgruppe ' + (i + 1) + ' von ' + n);
        b.dataset.page = i;
        b.addEventListener('click', function () {
          goTo(parseInt(this.dataset.page, 10));
          restart();
        });
        li.appendChild(b);
        dotsBox.appendChild(li);
      }
      syncUI();
    }

    function syncUI() {
      var p = pageIndex();
      var n = pageCount();
      var dots = dotsBox.querySelectorAll('button');
      for (var i = 0; i < dots.length; i++) {
        dots[i].setAttribute('aria-current', i === p ? 'true' : 'false');
      }
      if (prevBtn) prevBtn.disabled = p <= 0;
      if (nextBtn) nextBtn.disabled = p >= n - 1;
      if (live) live.textContent = 'Bildgruppe ' + (p + 1) + ' von ' + n + ' (' + slideCount + ' Bilder gesamt)';
    }

    /* Autoplay */
    function tick() {
      var p = pageIndex();
      goTo(p >= pageCount() - 1 ? 0 : p + 1);
    }
    function setPauseUI(running) {
      if (!pauseBtn) return;
      pauseBtn.setAttribute('aria-pressed', running ? 'false' : 'true');
      pauseBtn.setAttribute('aria-label', running ? 'Autoplay pausieren' : 'Autoplay starten');
      var pi = pauseBtn.querySelector('[data-icon-pause]');
      var pl = pauseBtn.querySelector('[data-icon-play]');
      if (pi) pi.hidden = !running;
      if (pl) pl.hidden = running;
    }
    function play() {
      if (paused || timer) return;
      timer = setInterval(tick, interval);
      setPauseUI(true);
    }
    function stop() {
      clearInterval(timer); timer = null;
      setPauseUI(false);
    }
    function restart() { if (!paused) { stop(); play(); } }

    if (pauseBtn) {
      if (paused) pauseBtn.hidden = true; // kein Autoplay -> kein Pause-Knopf
      pauseBtn.addEventListener('click', function () {
        paused = !paused;
        if (paused) { stop(); } else { play(); }
      });
    }

    if (prevBtn) prevBtn.addEventListener('click', function () { goTo(pageIndex() - 1); restart(); });
    if (nextBtn) nextBtn.addEventListener('click', function () { goTo(pageIndex() + 1); restart(); });

    track.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') { e.preventDefault(); goTo(pageIndex() + 1); restart(); }
      if (e.key === 'ArrowLeft') { e.preventDefault(); goTo(pageIndex() - 1); restart(); }
    });

    // Autoplay pausiert bei Interaktion / unsichtbarem Tab
    ['mouseenter', 'focusin', 'touchstart', 'pointerdown'].forEach(function (ev) {
      root.addEventListener(ev, stop, { passive: true });
    });
    ['mouseleave', 'focusout'].forEach(function (ev) {
      root.addEventListener(ev, function () { if (!paused) play(); });
    });
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) { stop(); } else if (!paused) { play(); }
    });

    var scrollT;
    track.addEventListener('scroll', function () {
      clearTimeout(scrollT);
      scrollT = setTimeout(syncUI, 80);
    }, { passive: true });

    var resizeT;
    window.addEventListener('resize', function () {
      clearTimeout(resizeT);
      resizeT = setTimeout(buildDots, 150);
    });

    buildDots();
    if (document.readyState !== 'complete') {
      window.addEventListener('load', buildDots, { once: true });
    }
    if (!paused) play();
  })();

  /* -------- Hero-Slider (Fade) -------- */
  (function heroSlider() {
    var root = document.querySelector('[data-hero-slider]');
    if (!root) return;
    var imgs = root.querySelectorAll('[data-hero-img]');
    if (!imgs.length) return;

    var interval = parseInt(root.getAttribute('data-autoplay'), 10) || 4000;
    var current = 0;
    var dots = root.querySelectorAll('[data-hero-dot]');
    var prevBtn = root.querySelector('[data-hero-prev]');
    var nextBtn = root.querySelector('[data-hero-next]');
    var timer = null;
    var stopped = reduceMotion;

    function show(i) {
      imgs[current].classList.remove('is-active');
      if (dots[current]) dots[current].classList.remove('is-active');
      current = (i + imgs.length) % imgs.length;
      imgs[current].classList.add('is-active');
      if (dots[current]) dots[current].classList.add('is-active');
    }
    function next() { show(current + 1); }
    function prev() { show(current - 1); }
    function play() {
      if (stopped || timer) return;
      timer = setInterval(next, interval);
    }
    function stop() { clearInterval(timer); timer = null; }
    function restart() { if (!stopped) { stop(); play(); } }

    if (stopped) show(0);
    else {
      for (var d = 0; d < dots.length; d++) {
        (function (d) {
          dots[d].addEventListener('click', function () {
            show(d);
            restart();
          });
        }(d));
      }
      if (prevBtn) prevBtn.addEventListener('click', function () { prev(); restart(); });
      if (nextBtn) nextBtn.addEventListener('click', function () { next(); restart(); });
      root.addEventListener('mouseenter', stop, { passive: true });
      root.addEventListener('mouseleave', play, { passive: true });
      play();
    }
  })();

  /* -------- Lightbox (Popup-Slider je Kategorie) -------- */
  (function lightbox() {
    var root = document.querySelector('[data-gallery]');
    var lb = document.querySelector('[data-lightbox]');
    var openers = null;
    if (!root || !lb) return;

    var categories = [];
    try {
      var raw = root.getAttribute('data-gallery-categories');
      if (raw) categories = JSON.parse(raw);
    } catch (e) { categories = []; }
    if (!categories.length) return;

    var img = lb.querySelector('.lightbox__img');
    var caption = lb.querySelector('.lightbox__caption');
    var counter = lb.querySelector('[data-lightbox-counter]');
    var prevBtn = lb.querySelector('[data-lightbox-prev]');
    var nextBtn = lb.querySelector('[data-lightbox-next]');
    var closeBtn = lb.querySelector('[data-lightbox-close]');
    var open = document.querySelector('.gallery__track');

    var catIndex = 0;
    var imgIndex = 0;
    var lastFocus = null;

    function current() { return categories[catIndex].images || []; }

    function render() {
      var imgs = current();
      var item = imgs[Math.min(imgIndex, imgs.length - 1)];
      img.src = item.src;
      img.alt = item.alt || '';
      caption.textContent = categories[catIndex].category;
      counter.textContent = (imgIndex + 1) + ' / ' + imgs.length;
      prevBtn.disabled = false;
      nextBtn.disabled = false;
    }

    function openAt(idx) {
      catIndex = idx;
      imgIndex = 0;
      lastFocus = document.activeElement;
      lb.hidden = false;
      document.body.style.overflow = 'hidden';
      render();
      closeBtn.focus();
    }
    function close() {
      lb.hidden = true;
      document.body.style.overflow = '';
      if (lastFocus && lastFocus.focus) lastFocus.focus();
    }
    function step(d) {
      var imgs = current();
      var n = imgs.length;
      imgIndex = (imgIndex + d + n) % n;
      render();
    }

    if (root) {
      openers = root.querySelectorAll('[data-gallery-open]');
      for (var i = 0; i < openers.length; i++) {
        (function (i) {
          openers[i].addEventListener('click', function () { openAt(i); });
        }(i));
      }
    }
    if (closeBtn) closeBtn.addEventListener('click', close);
    if (prevBtn) prevBtn.addEventListener('click', function () { step(-1); });
    if (nextBtn) nextBtn.addEventListener('click', function () { step(1); });
    lb.addEventListener('click', function (e) { if (e.target === lb) close(); });
    document.addEventListener('keydown', function (e) {
      if (lb.hidden) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowRight') { e.preventDefault(); step(1); }
      if (e.key === 'ArrowLeft') { e.preventDefault(); step(-1); }
    });
  })();

  /* -------- Rezensionen-Slider -------- */
  (function testimonials() {
    var wrap = document.querySelector('[data-testi]');
    if (!wrap) return;
    var track = wrap.querySelector('[data-testi-track]');
    var prevBtn = wrap.querySelector('[data-testi-prev]');
    var nextBtn = wrap.querySelector('[data-testi-next]');
    var dotsWrap = wrap.querySelector('[data-testi-dots]');
    var slides = track.children;
    if (!slides.length) return;

    function pageCount() {
      return Math.max(1, Math.round(track.scrollWidth / Math.max(1, track.clientWidth)));
    }
    function pageIndex() {
      var maxLeft = track.scrollWidth - track.clientWidth;
      if (maxLeft <= 1) return 0;
      if (track.scrollLeft >= maxLeft - 1) return pageCount() - 1;
      return Math.round(track.scrollLeft / track.clientWidth);
    }
    function goTo(i) {
      var n = pageCount();
      i = Math.max(0, Math.min(n - 1, i));
      track.scrollTo({ left: Math.min(i * track.clientWidth, track.scrollWidth - track.clientWidth) });
    }

    function buildDots() {
      dotsWrap.innerHTML = '';
      var n = pageCount();
      for (var i = 0; i < n; i++) {
        var li = document.createElement('li');
        var b = document.createElement('button');
        b.type = 'button';
        b.setAttribute('aria-label', 'Ansicht ' + (i + 1) + ' von ' + n);
        b.addEventListener('click', (function (i) { return function () { goTo(i); }; }(i)));
        li.appendChild(b);
        dotsWrap.appendChild(li);
      }
      sync();
    }

    function sync() {
      var p = pageIndex();
      var n = pageCount();
      var dots = dotsWrap.querySelectorAll('button');
      for (var i = 0; i < dots.length; i++) {
        dots[i].setAttribute('aria-current', i === p ? 'true' : 'false');
      }
      if (prevBtn) prevBtn.disabled = p <= 0;
      if (nextBtn) nextBtn.disabled = p >= n - 1;
    }

    if (prevBtn) prevBtn.addEventListener('click', function () { goTo(pageIndex() - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function () { goTo(pageIndex() + 1); });

    var t;
    track.addEventListener('scroll', function () { clearTimeout(t); t = setTimeout(sync, 80); }, { passive: true });
    var rt;
    window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(buildDots, 150); });

    track.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') { e.preventDefault(); goTo(pageIndex() + 1); }
      if (e.key === 'ArrowLeft') { e.preventDefault(); goTo(pageIndex() - 1); }
    });

    buildDots();
    if (document.readyState !== 'complete') {
      window.addEventListener('load', buildDots, { once: true });
    }
  })();

  /* -------- Kontaktformular: Inline-Validierung + Statusmeldung -------- */
  (function contact() {
    var form = document.querySelector('[data-contact]');
    if (!form) return;
    var status = form.querySelector('[data-contact-status]');

    var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    var PHONE_RE = /^[+()\d][\d\s()+\-/. ]{4,}$/;

    var fields = {
      name: { input: form.elements.name, required: true, validate: function (v) { return v.trim().length >= 2; }, msg: 'Bitte geben Sie Ihren Namen ein.' },
      email: { input: form.elements.email, required: true, validate: function (v) { return EMAIL_RE.test(v.trim()); }, msg: 'Bitte geben Sie eine gültige E-Mail-Adresse ein.' },
      phone: { input: form.elements.phone, required: false, validate: function (v) { return v.trim() === '' || PHONE_RE.test(v.trim()); }, msg: 'Bitte geben Sie eine gültige Telefonnummer ein.' },
      message: { input: form.elements.message, required: true, validate: function (v) { return v.trim().length >= 5; }, msg: 'Bitte beschreiben Sie Ihr Vorhaben kurz.' }
    };

    function errorNode(name) {
      return form.querySelector('#' + name + '-error');
    }
    function setError(name, message) {
      var f = fields[name];
      var err = errorNode(name);
      if (!err) return;
      var fieldWrap = f.input ? f.input.closest('.field') : null;
      var hasError = !!message;
      if (f.input) f.input.setAttribute('aria-invalid', hasError ? 'true' : 'false');
      if (fieldWrap) fieldWrap.classList.toggle('has-error', hasError);
      if (hasError) {
        err.textContent = message;
        err.hidden = false;
      } else {
        err.hidden = true;
      }
    }
    function validateField(name) {
      var f = fields[name];
      if (!f || !f.input) return true;
      var value = f.input.value || '';
      var ok = true;
      if (f.required && value.trim() === '') ok = false;
      else if (value.trim() !== '' && !f.validate(value)) ok = false;
      setError(name, ok ? '' : f.msg);
      return ok;
    }

    Object.keys(fields).forEach(function (name) {
      var f = fields[name];
      if (!f.input) return;
      var t;
      f.input.addEventListener('blur', function () { validateField(name); });
      f.input.addEventListener('input', function () {
        clearTimeout(t);
        t = setTimeout(function () { validateField(name); }, 300);
      });
    });

    form.addEventListener('submit', function (e) {
      // Honeypot
      if (form.website && form.website.value) { e.preventDefault(); return; }

      var firstInvalid = null;
      var valid = true;
      Object.keys(fields).forEach(function (name) {
        if (!validateField(name)) { valid = false; if (!firstInvalid) firstInvalid = fields[name].input; }
      });

      if (!valid) {
        e.preventDefault();
        if (firstInvalid) firstInvalid.focus();
        status.dataset.state = 'error';
        status.textContent = 'Bitte korrigieren Sie die markierten Felder.';
        return;
      }

      status.dataset.state = '';
      status.textContent = '';
      /* --- Ohne Backend: hier greift der normale POST an action="/api/kontakt".
             Für eine reine Static-Site einen Formular-Dienst eintragen
             (Formspree, Web3Forms, Netlify Forms, Cloudflare Pages Functions …)
             oder das folgende fetch() aktivieren und action anpassen. --- */
      // e.preventDefault();
      // fetch(form.action, { method:'POST', body:new FormData(form), headers:{Accept:'application/json'} })
      //   .then(function(r){ if(!r.ok) throw 0;
      //     form.reset();
      //     status.dataset.state='ok';
      //     status.textContent='Vielen Dank – wir melden uns in Kürze bei Ihnen.';
      //   })
      //   .catch(function(){
      //     status.dataset.state='error';
      //     status.textContent='Senden fehlgeschlagen. Bitte rufen Sie uns an: 08624 1260.';
      //   });
    });
  })();

  /* -------- Sticky-Bar E-Mail: Betreff + Datum/Uhrzeit im Body -------- */
  (function stickyMail() {
    var links = document.querySelectorAll('[data-sticky-mail]');
    if (!links.length) return;
    var base = (links[0].getAttribute('href') || 'mailto:').replace(/[?#].*$/, '');

    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function deutschDate(d) {
      var wd = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'][d.getDay()];
      var mo = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'][d.getMonth()];
      return wd + ', ' + d.getDate() + '. ' + mo + ' ' + d.getFullYear();
    }
    function build() {
      var now = new Date();
      var date = deutschDate(now);
      var time = pad(now.getHours()) + ':' + pad(now.getMinutes());
      var subject = 'Anfrage vom ' + date + ', ' + time + ' Uhr';
      var body = 'Guten Tag,\n\n'
        + 'ich schreibe Ihnen am ' + date + ' um ' + time + ' Uhr.\n\n'
        + 'Sehr gerne würde ich mich über Ihre Schreinerei und Ihr Angebot informieren.\n\n'
        + 'Meine Anfrage:\n\n\n'
        + 'Viele Grüße\n\n';
      return base + '?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
    }

    for (var i = 0; i < links.length; i++) {
      (function (link) {
        link.addEventListener('click', function () {
          link.setAttribute('href', build());
        });
      }(links[i]));
    }
  })();

  /* -------- Cookie-Banner (Opt-in/Opt-out) -------- */
  (function cookies() {
    var banner = document.querySelector('[data-cookie-banner]');
    if (!banner) return;
    var KEY = 'sf_cookie_consent';

    function setChoice(choice) {
      try { localStorage.setItem(KEY, choice); } catch (e) { /* noop */ }
      banner.hidden = true;
    }

    if (localStorage.getItem(KEY)) { banner.hidden = true; return; }

    // Keine Entscheidung vorhanden -> Banner anzeigen
    banner.hidden = false;

    var accept = banner.querySelector('[data-cookie-accept]');
    var decline = banner.querySelector('[data-cookie-decline]');
    if (accept) accept.addEventListener('click', function () { setChoice('opt-in'); });
    if (decline) decline.addEventListener('click', function () { setChoice('opt-out'); });
  })();

  /* -------- To-the-top Button -------- */
  (function toTop() {
    var btn = document.querySelector('[data-to-top]');
    if (!btn) return;

    btn.disabled = true;
    function update() {
      btn.disabled = (window.scrollY < 300);
    }
    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update, { passive: true });
    update();

    btn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
    });
  })();

  /* -------- Google-Maps: erst um Erlaubnis fragen -------- */
  (function mapConsent() {
    var openBtn = document.querySelector('[data-map-open]');
    var dialog = document.querySelector('[data-map-dialog]');
    if (!openBtn || !dialog) return;
    var url = openBtn.getAttribute('data-map-url');
    var confirmBtn = dialog.querySelector('[data-map-confirm]');
    var cancelBtn = dialog.querySelector('[data-map-cancel]');
    var lastFocus = null;

    function openDialog() {
      lastFocus = document.activeElement;
      dialog.hidden = false;
      document.body.style.overflow = 'hidden';
      if (confirmBtn) confirmBtn.focus();
    }
    function closeDialog() {
      dialog.hidden = true;
      document.body.style.overflow = '';
      if (lastFocus && lastFocus.focus) lastFocus.focus();
    }

    openBtn.addEventListener('click', openDialog);
    if (cancelBtn) cancelBtn.addEventListener('click', closeDialog);
    if (confirmBtn) confirmBtn.addEventListener('click', function () {
      closeDialog();
      if (url) window.open(url, '_blank', 'noopener');
    });
    dialog.addEventListener('click', function (e) { if (e.target === dialog) closeDialog(); });
    document.addEventListener('keydown', function (e) {
      if (dialog.hidden) return;
      if (e.key === 'Escape') closeDialog();
    });
  })();
})();
