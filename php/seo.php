<?php

/**
 * Firmenname xxx – serverseitiges SEO-Head + JSON-LD.
 *
 * Erzeugt Titel, Meta-Description, Open Graph, Twitter Card, Canonical,
 * Preload des LCP-Bildes sowie das Structured Data (@graph) direkt im
 * HTML – dadurch vollständig crawler-/KI-lesbar ohne JavaScript.
 */

declare(strict_types=1);

function seo_structured_data(array $s): array
{
    $base = site_base($s);
    $b = $s['business'] ?? [];
    $meta = $s['meta'] ?? [];

    $abs = function (string $p) use ($base, $meta): string {
        if ($p === '' || strpos($p, 'http') === 0) {
            return $p;
        }
        // Bei Angabe von ogImage absolute machen
        return $base . '/' . ltrim($p, '/');
    };

    $graph = [];

    $graph[] = [
        '@type' => 'Organization',
        '@id' => $base . '/#organization',
        'name' => $b['name'] ?? '',
        'url' => $base . '/',
        'logo' => $base . site_asset($s, site_config($s, 'project.logo', 'site/logo-schreinerei-frank.png')),
        'email' => $b['email'] ?? '',
        'telephone' => $b['phoneSchema'] ?? '',
        'founder' => ['@type' => 'Person', 'name' => $b['owner'] ?? ''],
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $b['street'] ?? '',
            'postalCode' => $b['postalCode'] ?? '',
            'addressLocality' => $b['city'] ?? '',
            'addressRegion' => $b['region'] ?? '',
            'addressCountry' => $b['country'] ?? '',
        ],
    ];

    $graph[] = [
        '@type' => 'WebSite',
        '@id' => $base . '/#website',
        'url' => $base . '/',
        'name' => $b['name'] ?? '',
        'inLanguage' => 'de-DE',
        'publisher' => ['@id' => $base . '/#organization'],
    ];

    $areaServed = [];
    foreach (($b['areaServed'] ?? []) as $item) {
        $areaServed[] = ['@type' => 'Place', 'name' => is_string($item) ? $item : ($item['ort'] ?? '')];
    }

    $hours = [];
    foreach (($b['openingHoursSpec'] ?? []) as $o) {
        $hours[] = [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => $o['days'] ?? [],
            'opens' => $o['opens'] ?? '',
            'closes' => $o['closes'] ?? '',
        ];
    }

    $offers = [];
    foreach (($s['services'] ?? []) as $sv) {
        $offers[] = [
            '@type' => 'Offer',
            'itemOffered' => [
                '@type' => 'Service',
                'name' => $sv['title'] ?? '',
                'serviceType' => $sv['serviceType'] ?? '',
            ],
        ];
    }

    $geo = $b['geo'] ?? [];
    $graph[] = [
        '@type' => ['LocalBusiness', 'Carpenter', 'HomeAndConstructionBusiness'],
        '@id' => $base . '/#business',
        'name' => $b['name'] ?? '',
        'image' => $abs($meta['ogImage'] ?? ''),
        'url' => $base . '/',
        'telephone' => $b['phoneSchema'] ?? '',
        'faxNumber' => $b['faxSchema'] ?? '',
        'email' => $b['email'] ?? '',
        'priceRange' => $b['priceRange'] ?? '',
        'parentOrganization' => ['@id' => $base . '/#organization'],
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $b['street'] ?? '',
            'postalCode' => $b['postalCode'] ?? '',
            'addressLocality' => $b['city'] ?? '',
            'addressRegion' => $b['region'] ?? '',
            'addressCountry' => $b['country'] ?? '',
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => $geo['lat'] ?? 0,
            'longitude' => $geo['lng'] ?? 0,
        ],
        'hasMap' => $b['mapUrl'] ?? '',
        'areaServed' => $areaServed,
        'openingHoursSpecification' => $hours,
        'hasOfferCatalog' => [
            '@type' => 'OfferCatalog',
            'name' => 'Leistungen der ' . ($b['name'] ?? ''),
            'itemListElement' => $offers,
        ],
    ];

    $graph[] = [
        '@type' => 'WebPage',
        '@id' => $base . '/#webpage',
        'url' => $base . '/',
        'name' => $meta['title'] ?? '',
        'isPartOf' => ['@id' => $base . '/#website'],
        'about' => ['@id' => $base . '/#business'],
        'inLanguage' => 'de-DE',
        'primaryImageOfPage' => $abs($meta['ogImage'] ?? ''),
    ];

    $graph[] = [
        '@type' => 'BreadcrumbList',
        '@id' => $base . '/#breadcrumb',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Start', 'item' => $base . '/'],
        ],
    ];

    $faq = [];
    foreach (site_active_filter($s['faq'] ?? []) as $f) {
        $faq[] = [
            '@type' => 'Question',
            'name' => $f['q'] ?? '',
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a'] ?? ''],
        ];
    }
    if ($faq) {
        $graph[] = [
            '@type' => 'FAQPage',
            '@id' => $base . '/#faq',
            'mainEntity' => $faq,
        ];
    }

    return ['@context' => 'https://schema.org', '@graph' => $graph];
}

/**
 * Gibt den kompletten <head>-Inhalt (ab <title>) zurück.
 * $page: '' (Start), 'impressum', 'datenschutz'
 */
function seo_head(array $s, string $page = ''): string
{
    $meta = $s['meta'] ?? [];
    $business = $s['business'] ?? [];

    $isLegal = ($page === 'impressum' || $page === 'datenschutz');
    $title = $isLegal
        ? site_get_str($s, 'legal.' . $page . '.title', $meta['title'] ?? '')
        : site_get_str($s, 'meta.title', '');
    $description = $isLegal
        ? site_get_str($s, 'legal.' . $page . '.metaDescription', $meta['description'] ?? '')
        : site_get_str($s, 'meta.description', '');

    $base = site_base($s);
    $canonical = $base . ($page === '' ? '/' : '/' . $page . '/');
    $ogTitle = site_get_str($s, 'meta.ogTitle', $title);
    $ogDesc = site_get_str($s, 'meta.ogDescription', $description);
    $ogImage = site_abs($s, site_get_str($s, 'meta.ogImage', ''));
    $lcp = site_get_str($s, 'meta.lcpImage', site_asset($s, 'content/kuechen/projekt-kueche.jpg'));

    $h = '';
    $h .= '  <title>' . site_esc($title) . "</title>\n";
    $h .= '  <meta name="description" content="' . site_esc($description) . "\">\n";
    $h .= '  <link rel="canonical" href="' . site_esc($canonical) . "\">\n";
    $h .= '  <meta name="robots" content="index,follow,max-image-preview:large,max-snippet:-1">' . "\n";
    $h .= '  <meta name="theme-color" content="' . site_esc(site_config($s, 'project.themeColor', '#3D6490')) . "\">\n";
    $h .= '  <meta name="author" content="' . site_esc(($business['name'] ?? '') . ', Inhaber ' . ($business['owner'] ?? '')) . "\">\n";
    $h .= "\n";
    $h .= '  <meta property="og:type" content="website">' . "\n";
    $h .= '  <meta property="og:locale" content="de_DE">' . "\n";
    $h .= '  <meta property="og:site_name" content="' . site_esc($business['name'] ?? '') . "\">\n";
    $h .= '  <meta property="og:title" content="' . site_esc($ogTitle) . "\">\n";
    $h .= '  <meta property="og:description" content="' . site_esc($ogDesc) . "\">\n";
    $h .= '  <meta property="og:url" content="' . site_esc($canonical) . "\">\n";
    $h .= '  <meta property="og:image" content="' . site_esc($ogImage) . "\">\n";
    $h .= '  <meta property="og:image:width" content="1200">' . "\n";
    $h .= '  <meta property="og:image:height" content="630">' . "\n";
    $h .= '  <meta property="og:image:alt" content="' . site_esc($business['name'] ?? '') . "\">\n";
    $h .= '  <meta name="twitter:card" content="summary_large_image">' . "\n";
    $h .= '  <meta name="twitter:title" content="' . site_esc($ogTitle) . "\">\n";
    $h .= '  <meta name="twitter:description" content="' . site_esc($ogDesc) . "\">\n";
    $h .= '  <meta name="twitter:image" content="' . site_esc($ogImage) . "\">\n";
    $h .= "\n";
    $h .= '  <link rel="icon" href="/favicon.ico">' . "\n";
    $h .= '  <link rel="apple-touch-icon" href="' . site_asset($s, 'site/apple-touch-icon.png') . "\">\n";
    $h .= '  <link rel="manifest" href="/site.webmanifest">' . "\n";
    $h .= "\n";
    $h .= '  <link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    $h .= '  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    $h .= '  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fira+Sans:wght@400;500;600;700&family=Spectral:ital,wght@1,400&display=swap" media="print" onload="this.media=\'all\'">' . "\n";
    $h .= '  <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fira+Sans:wght@400;500;600;700&family=Spectral:ital,wght@1,400&display=swap"></noscript>' . "\n";
    $h .= "\n";
    $h .= '  <link rel="preload" as="image" href="' . site_esc($lcp) . '" fetchpriority="high">' . "\n";
    $h .= '  <link rel="stylesheet" href="' . site_asset($s, 'css/styles.css') . "\">\n";
    $h .= '  <script id="structured-data" type="application/ld+json">' . "\n";
    $h .= json_encode(seo_structured_data($s), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    $h .= "  </script>\n";

    return $h;
}
