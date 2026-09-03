<?php

/**
 * Firmenname xxx – Home (Startseiten-Sektionen).
 * Erwartet: $s (Daten). Rendert serverseitig Hero, Trust, Leistungen,
 * Galerie, Über uns, FAQ, Rezensionen und Kontakt.
 */

declare(strict_types=1);

$hero = $s['hero'] ?? [];
$business = $s['business'] ?? [];
$contact = $s['contact'] ?? [];
$ui = $s['ui'] ?? [];

$heroImages = site_active_filter(array_slice($hero['images'] ?? [], 0)); // Slider-Bilder
$gallery = site_active_filter($s['gallery'] ?? []);
$services = $s['services'] ?? [];
$trust = $s['trust'] ?? [];
$faq = site_active_filter($s['faq'] ?? []);
$testimonials = $s['testimonials'] ?? [];
?>

<!-- ============ HERO ============ -->
<section class="hero" id="start">
  <div class="wrap hero__inner">
    <div>
      <span class="kicker"><?php echo site_esc(site_get_str($hero, 'kicker')); ?></span>
      <h1><?php echo site_esc(site_get_str($hero, 'heading')); ?></h1>
      <p class="hero__claim"><?php echo site_esc(site_get_str($hero, 'claim')); ?></p>
      <p class="hero__lead"><?php echo site_esc(site_get_str($hero, 'lead')); ?></p>
      <div class="hero__cta">
        <a class="btn" href="#kontakt"><?php echo site_esc(site_get_str($hero, 'ctaPrimary')); ?></a>
        <a class="btn btn--ghost" href="#galerie"><?php echo site_esc(site_get_str($hero, 'ctaSecondary')); ?></a>
      </div>
    </div>
    <div class="hero__media">
      <div class="hero-slider" data-hero-slider data-autoplay="4000">
        <?php foreach ($heroImages as $i => $img): ?>
          <picture class="hero-slider__img<?php echo $i === 0 ? ' is-active' : ''; ?>" data-hero-img>
            <img class="hero-slider__image" width="1280" height="960"
              src="<?php echo site_esc(site_asset($s, site_get_str($img, 'src'))); ?>"
              alt="<?php echo site_esc(site_get_str($img, 'alt')); ?>"
              <?php echo $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"'; ?>
              decoding="async">
          </picture>
        <?php endforeach; ?>
        <button class="hero-slider__arrow hero-slider__arrow--prev" type="button" data-hero-prev aria-label="<?php echo site_esc(site_get_str($ui, 'heroPrevAria', 'Vorheriges Bild')); ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M15 5l-7 7 7 7" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>
        <button class="hero-slider__arrow hero-slider__arrow--next" type="button" data-hero-next aria-label="<?php echo site_esc(site_get_str($ui, 'heroNextAria', 'Nächstes Bild')); ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>
        <div class="hero-slider__dots" role="list" aria-label="<?php echo site_esc(site_get_str($ui, 'heroDotsAria', 'Hero-Bilder')); ?>">
          <?php for ($i = 0; $i < count($heroImages); $i++): ?>
            <button class="hero-slider__dot<?php echo $i === 0 ? ' is-active' : ''; ?>" type="button" data-hero-dot aria-label="<?php echo site_esc(str_replace(['{aktuell}', '{gesamt}'], [$i + 1, count($heroImages)], site_get_str($ui, 'heroDotAria', 'Bild {aktuell} von {gesamt}'))); ?>"></button>
          <?php endfor; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ TRUST ROW ============ -->
<div class="trust">
  <div class="wrap trust__inner">
    <?php foreach ($trust as $item): ?>
      <div class="trust__item"><b><?php echo site_esc(site_get_str($item, 'value')); ?></b><span><?php echo site_esc(site_get_str($item, 'label')); ?></span></div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ============ LEISTUNGEN ============ -->
<section class="section" id="leistungen">
  <div class="wrap">
    <div class="section__head">
      <span class="kicker"><?php echo site_esc(site_get_str($s['servicesIntro'] ?? [], 'kicker')); ?></span>
      <h2><?php echo site_esc(site_get_str($s['servicesIntro'] ?? [], 'heading')); ?></h2>
      <p><?php echo site_esc(site_get_str($s['servicesIntro'] ?? [], 'text')); ?></p>
    </div>
    <div class="cards">
      <?php foreach ($services as $item): ?>
        <article class="card">
          <div class="card__icon"><svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="<?php echo site_esc(site_get_str($item, 'icon')); ?>"></path>
            </svg></div>
          <h3><?php echo site_esc(site_get_str($item, 'title')); ?></h3>
          <p><?php echo site_esc(site_get_str($item, 'text')); ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ GALERIE ============ -->
<section class="section section--band" id="galerie">
  <div class="wrap">
    <div class="section__head">
      <span class="kicker"><?php echo site_esc(site_get_str($s['galleryIntro'] ?? [], 'kicker')); ?></span>
      <h2><?php echo site_esc(site_get_str($s['galleryIntro'] ?? [], 'heading')); ?></h2>
    </div>

    <div class="gallery" data-gallery data-autoplay="6000" data-gallery-categories="<?php echo site_esc(json_encode($gallery, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); ?>">
      <ul class="gallery__track" role="group" aria-roledescription="Karussell" aria-label="<?php echo site_esc(site_get_str($ui, 'galleryTrackAria', 'Ausgeführte Projekte')); ?>" tabindex="0">
        <?php foreach ($gallery as $i => $item): ?>
          <li class="gallery__slide">
            <figure>
              <div class="gallery__thumb">
                <button class="gallery__open" type="button" data-gallery-open="<?php echo $i; ?>" aria-haspopup="dialog" aria-label="<?php echo site_esc(site_get_str($ui, 'galleryOpenAria', 'Bild vergrößern')); ?>">
                  <picture>
                    <img width="1000" height="1000" loading="lazy" decoding="async"
                      src="<?php echo site_esc(site_asset($s, site_get_str($item, 'mainImage'))); ?>"
                      alt="<?php echo site_esc(site_get_str($item, 'mainAlt')); ?>">
                  </picture>
                </button>
                <button class="gallery__zoom" type="button" data-gallery-open="<?php echo $i; ?>" aria-haspopup="dialog" aria-label="<?php echo site_esc(site_get_str($ui, 'galleryZoomAria', 'Galerie ansehen')); ?>">
                  <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" />
                    <path d="m21 21-4.3-4.3" />
                    <path d="M8 11h6M11 8v6" />
                  </svg>
                </button>
              </div>
              <figcaption><?php echo site_esc(site_get_str($item, 'mainAlt')); ?></figcaption>
            </figure>
          </li>
        <?php endforeach; ?>
      </ul>

      <div class="gallery__controls">
        <ul class="gallery__dots" data-gallery-dots aria-label="<?php echo site_esc(site_get_str($ui, 'galleryDotsAria', 'Zu Bildgruppe springen')); ?>"></ul>
        <div class="gallery__nav">
          <button type="button" data-gallery-prev aria-label="<?php echo site_esc(site_get_str($ui, 'galleryPrevAria', 'Vorherige Bilder')); ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M15 5l-7 7 7 7" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </button>
          <button type="button" data-gallery-pause class="gallery__pause" aria-pressed="false" aria-label="<?php echo site_esc(site_get_str($ui, 'galleryPauseAria', 'Autoplay pausieren')); ?>">
            <svg class="gallery__pause-icon" data-icon-pause viewBox="0 0 24 24" aria-hidden="true">
              <path d="M7 5v14M17 5v14" />
            </svg>
            <svg class="gallery__play-icon" data-icon-play viewBox="0 0 24 24" aria-hidden="true" hidden>
              <path d="M8 5l12 7-12 7Z" />
            </svg>
          </button>
          <button type="button" data-gallery-next aria-label="<?php echo site_esc(site_get_str($ui, 'galleryNextAria', 'Nächste Bilder')); ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </button>
        </div>
      </div>
      <p class="gallery__hint"><?php echo site_esc(site_get_str($ui, 'galleryHint', 'Der Slider bewegt sich von links nach rechts · Bild antippen zum Vergrößern · in der Galerie über die Pfeile bzw. die Lupe alle Fotos der Kategorie durchblättern.')); ?></p>
      <p class="sr-only" role="status" aria-live="polite" data-gallery-live></p>
    </div>

    <div class="lightbox" data-lightbox role="dialog" aria-modal="true" aria-label="<?php echo site_esc(site_get_str($ui, 'lightboxAria', 'Bildergalerie')); ?>" hidden>
      <button class="lightbox__close" type="button" data-lightbox-close aria-label="<?php echo site_esc(site_get_str($ui, 'lightboxCloseAria', 'Schließen')); ?>">×</button>
      <div class="lightbox__stage">
        <button class="lightbox__arrow lightbox__arrow--prev" type="button" data-lightbox-prev aria-label="<?php echo site_esc(site_get_str($ui, 'lightboxPrevAria', 'Vorheriges Bild')); ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M15 5l-7 7 7 7" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>
        <figure class="lightbox__figure">
          <img class="lightbox__img" src="" alt="">
          <figcaption class="lightbox__caption"></figcaption>
        </figure>
        <button class="lightbox__arrow lightbox__arrow--next" type="button" data-lightbox-next aria-label="<?php echo site_esc(site_get_str($ui, 'lightboxNextAria', 'Nächstes Bild')); ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>
      </div>
      <p class="lightbox__counter" data-lightbox-counter aria-live="polite"></p>
      <div class="lightbox__thumbs" data-lightbox-thumbs role="list" aria-label="<?php echo site_esc(site_get_str($ui, 'lightboxThumbsAria', 'Galerie-Kategorien')); ?>"></div>
    </div>
  </div>
</section>

<!-- ============ ÜBER UNS ============ -->
<section class="section" id="ueber-uns">
  <div class="wrap">
    <div class="section__head">
      <span class="kicker"><?php echo site_esc(site_get_str(($s['about'] ?? []), 'kicker')); ?></span>
      <h2><?php echo site_esc(site_get_str(($s['about'] ?? []), 'heading')); ?></h2>
    </div>
    <div class="about">
      <div>
        <p><?php echo site_esc(site_get_str(($s['about'] ?? []), 'text')); ?></p>
        <ul class="checklist">
          <?php foreach (($s['about']['points'] ?? []) as $pt): ?>
            <li><?php echo site_esc($pt); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="person">
        <img class="person__portrait" width="300" height="300" loading="lazy" decoding="async"
          src="<?php echo site_esc(site_asset($s, site_get_str(($s['about'] ?? []), 'personImage'))); ?>"
          alt="<?php echo site_esc(site_get_str(($s['about'] ?? []), 'personImageAlt')); ?>">
        <b><?php echo site_esc(site_get_str($business, 'owner')); ?></b>
        <span><?php echo site_esc(site_get_str(($s['about'] ?? []), 'personRole')); ?></span>
        <a class="btn btn--ghost btn--block" href="#kontakt"><?php echo site_esc(site_get_str(($s['about'] ?? []), 'personCta')); ?></a>
      </div>
    </div>
  </div>
</section>

<!-- ============ FAQ ============ -->
<section class="section section--band" id="faq">
  <div class="wrap">
    <div class="section__head">
      <span class="kicker"><?php echo site_esc(site_get_str(($s['faqIntro'] ?? []), 'kicker')); ?></span>
      <h2><?php echo site_esc(site_get_str(($s['faqIntro'] ?? []), 'heading')); ?></h2>
    </div>
    <div class="faq" data-faq>
      <?php foreach ($faq as $item): ?>
        <details>
          <summary><?php echo site_esc(site_get_str($item, 'q')); ?></summary>
          <p><?php echo site_esc(site_get_str($item, 'a')); ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ REZENSIONEN ============ -->
<section class="section" id="rezensionen">
  <div class="wrap">
    <div class="section__head">
      <span class="kicker"><?php echo site_esc(site_get_str(($s['testimonialsIntro'] ?? []), 'kicker')); ?></span>
      <h2><?php echo site_esc(site_get_str(($s['testimonialsIntro'] ?? []), 'heading')); ?></h2>
    </div>
    <div class="testi" data-testi>
      <ul class="testi__track" data-testi-track role="group" aria-roledescription="Karussell" aria-label="<?php echo site_esc(site_get_str($ui, 'testiAria', 'Kundenrezensionen')); ?>">
        <?php foreach ($testimonials as $item): ?>
          <li class="testi__slide">
            <blockquote class="testi__quote"><?php echo site_esc(site_get_str($item, 'quote')); ?></blockquote>
            <cite class="testi__source"><?php echo site_esc(site_get_str($item, 'source')); ?></cite>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="testi__nav">
        <button type="button" data-testi-prev aria-label="<?php echo site_esc(site_get_str($ui, 'testiPrevAria', 'Vorherige Rezension')); ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M15 5l-7 7 7 7" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>
        <div class="testi__dots" data-testi-dots role="list" aria-label="<?php echo site_esc(site_get_str($ui, 'testiDotsAria', 'Zu Rezension springen')); ?>"></div>
        <button type="button" data-testi-next aria-label="<?php echo site_esc(site_get_str($ui, 'testiNextAria', 'Nächste Rezension')); ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</section>

<!-- ============ KONTAKT ============ -->
<section class="section section--band" id="kontakt">
  <div class="wrap">
    <div class="section__head">
      <span class="kicker"><?php echo site_esc(site_get_str($contact, 'kicker')); ?></span>
      <h2><?php echo site_esc(site_get_str($contact, 'heading')); ?></h2>
    </div>
    <div class="contact">
      <form data-contact method="post" action="<?php echo site_esc(site_get_str($contact, 'formAction', '/api/kontakt')); ?>" novalidate>
        <div class="field">
          <label for="f-name"><?php echo site_esc(site_get_str($ui, 'formNameLabel', 'Name')); ?></label>
          <input id="f-name" name="name" type="text" autocomplete="name" required aria-describedby="f-name-error">
          <p class="field-error" id="f-name-error" hidden></p>
        </div>
        <div class="field">
          <label for="f-email"><?php echo site_esc(site_get_str($ui, 'formEmailLabel', 'E-Mail')); ?></label>
          <input id="f-email" name="email" type="email" autocomplete="email" required aria-describedby="f-email-error">
          <p class="field-error" id="f-email-error" hidden></p>
        </div>
        <div class="field">
          <label for="f-phone"><?php echo site_esc(site_get_str($ui, 'formPhoneLabel', 'Telefon')); ?></label>
          <input id="f-phone" name="phone" type="tel" autocomplete="tel" aria-describedby="f-phone-error">
          <p class="field-error" id="f-phone-error" hidden></p>
        </div>
        <div class="field">
          <label for="f-msg"><?php echo site_esc(site_get_str($ui, 'formMessageLabel', 'Ihr Vorhaben')); ?></label>
          <textarea id="f-msg" name="message" required placeholder="<?php echo site_esc(site_get_str($ui, 'formMessagePlaceholder', 'Küche, Treppe, Innenausbau …')); ?>" aria-describedby="f-msg-error"></textarea>
          <p class="field-error" id="f-msg-error" hidden></p>
        </div>
        <div class="field field--hp" aria-hidden="true">
          <label for="f-website"><?php echo site_esc(site_get_str($ui, 'formWebsiteLabel', 'Website (bitte frei lassen)')); ?></label>
          <input id="f-website" name="website" type="text" tabindex="-1" autocomplete="off">
        </div>
        <button class="btn btn--block" type="submit"><?php echo site_esc(site_get_str($ui, 'formSubmit', 'Anfrage senden')); ?></button>
        <p class="form-note"><?php echo site_esc(site_get_str($contact, 'consent')); ?> <?php echo site_esc(site_get_str($ui, 'formConsentIntro', 'Details in der')); ?> <a href="<?php echo $baseHref ?? './'; ?><?php echo site_esc(site_get_str($ui, 'formConsentLinkHref', 'datenschutz')); ?>/"><?php echo site_esc(site_get_str($ui, 'formConsentLink', 'Datenschutzerklärung')); ?></a>.</p>
        <p class="form-status" role="status" aria-live="polite" data-contact-status></p>
      </form>

      <div class="info-card">
        <dl>
          <dt><?php echo site_esc(site_get_str($ui, 'contactAddressLabel', 'Adresse')); ?></dt>
          <dd><?php echo site_esc(site_get_str($business, 'street')); ?><br><span><?php echo site_esc(site_get_str($business, 'postalCode')); ?></span> <span><?php echo site_esc(site_get_str($business, 'city')); ?></span></dd>
          <dt><?php echo site_esc(site_get_str($ui, 'contactPhoneLabel', 'Telefon')); ?></dt>
          <dd><a href="<?php echo site_esc(site_get_str($business, 'phoneHref', 'tel:')); ?>"><?php echo site_esc(site_get_str($business, 'phone')); ?></a></dd>
          <dt><?php echo site_esc(site_get_str($ui, 'contactFaxLabel', 'Telefax')); ?></dt>
          <dd><?php echo site_esc(site_get_str($business, 'fax')); ?></dd>
          <dt><?php echo site_esc(site_get_str($ui, 'contactEmailLabel', 'E-Mail')); ?></dt>
          <dd><a href="mailto:<?php echo site_esc(site_get_str($business, 'email')); ?>"><?php echo site_esc(site_get_str($business, 'email')); ?></a></dd>
          <dt><?php echo site_esc(site_get_str($ui, 'contactHoursLabel', 'Öffnungszeiten')); ?></dt>
          <dd><?php echo site_esc(site_get_str($business, 'openingHoursText')); ?></dd>
        </dl>
        <div class="map">
          <button class="map__btn" type="button" data-map-open data-map-url="<?php echo site_esc(site_get_str($business, 'mapUrl')); ?>" aria-haspopup="dialog" aria-label="<?php echo site_esc(site_get_str($ui, 'mapOpenAria', 'Karte von Google Maps öffnen')); ?>">
            <img src="<?php echo site_asset($s, 'content/kontakt/karte-seeon.svg'); ?>" width="800" height="480" loading="lazy" decoding="async" alt="<?php echo site_esc(site_get_str($ui, 'mapImageAlt', 'Kartenausschnitt Schreinerei Frank')); ?>">
            <span class="map__badge">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 20s-7-4.6-7-10a7 7 0 0 1 14 0c0 5.4-7 10-7 10Z" />
                <circle cx="12" cy="10" r="2.5" />
              </svg>
              <?php echo site_esc(site_get_str($ui, 'mapBadge', 'Karte öffnen')); ?>
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="map-dialog" data-map-dialog role="dialog" aria-modal="true" aria-labelledby="map-dialog-title" hidden>
  <div class="map-dialog__box">
    <h3 id="map-dialog-title"><?php echo site_esc(site_get_str($ui, 'mapDialogTitle', 'Google Maps öffnen?')); ?></h3>
    <p><?php echo site_esc(site_get_str($contact, 'mapNotice')); ?></p>
    <div class="map-dialog__actions">
      <button class="btn" type="button" data-map-confirm><?php echo site_esc(site_get_str($ui, 'mapDialogConfirm', 'Öffnen')); ?></button>
      <button class="btn btn--ghost" type="button" data-map-cancel><?php echo site_esc(site_get_str($ui, 'mapDialogCancel', 'Abbrechen')); ?></button>
    </div>
    <a class="map-dialog__link" href="<?php echo $baseHref ?? './'; ?><?php echo site_esc(site_get_str($ui, 'mapDialogLinkHref', 'datenschutz')); ?>/"><?php echo site_esc(site_get_str($ui, 'mapDialogLink', 'Datenschutzerklärung')); ?></a>
  </div>
</div>