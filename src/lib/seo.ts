/**
 * Serverseitiges SEO-Head + JSON-LD (Port von php/seo.php).
 *
 * Erzeugt Titel, Meta-Description, Open Graph, Twitter Card, Canonical,
 * Preload des LCP-Bildes sowie Structured Data (@graph) direkt im HTML.
 */
import {
    type SiteData,
    siteAbs,
    siteAnchor,
    siteAsset,
    siteConfig,
    siteGetStr,
    siteActiveFilter,
} from './data.ts';

/** Logo-/Asset-Pfad helper für Structured Data (domainfrei). */
const abs = (p: string): string => {
    if (p === '' || p.startsWith('http') || p.startsWith('//')) {
        return p;
    }
    return siteAbs(p);
};

/** Structured Data als @graph-Array (Port von seo_structured_data()). */
export function seoStructuredData(s: SiteData): unknown[] {
    const b = (s['business'] ?? {}) as Record<string, unknown>;
    const meta = (s['meta'] ?? {}) as Record<string, unknown>;

    const graph: unknown[] = [];

    graph.push({
        '@type': 'Organization',
        '@id': siteAnchor('organization'),
        name: b['name'] ?? '',
        url: siteAbs('/'),
        logo: siteAsset(s, siteConfig(s, 'project.logo', 'site/logo.png')),
        email: b['email'] ?? '',
        telephone: b['phoneSchema'] ?? '',
        founder: { '@type': 'Person', name: b['owner'] ?? '' },
        address: {
            '@type': 'PostalAddress',
            streetAddress: b['street'] ?? '',
            postalCode: b['postalCode'] ?? '',
            addressLocality: b['city'] ?? '',
            addressRegion: b['region'] ?? '',
            addressCountry: b['country'] ?? '',
        },
    });

    graph.push({
        '@type': 'WebSite',
        '@id': siteAnchor('website'),
        url: siteAbs('/'),
        name: b['name'] ?? '',
        inLanguage: 'de-DE',
        publisher: { '@id': siteAnchor('organization') },
    });

    const areaServed = [];
    for (const item of (b['areaServed'] ?? []) as unknown[]) {
        if (typeof item === 'string') {
            areaServed.push({ '@type': 'Place', name: item });
        } else if (item !== null && typeof item === 'object') {
            areaServed.push({
                '@type': 'Place',
                name: (item as { ort?: string })['ort'] ?? '',
            });
        }
    }

    const hours = [];
    for (const o of (b['openingHoursSpec'] ?? []) as { days?: unknown; opens?: string; closes?: string }[]) {
        hours.push({
            '@type': 'OpeningHoursSpecification',
            dayOfWeek: o['days'] ?? [],
            opens: o['opens'] ?? '',
            closes: o['closes'] ?? '',
        });
    }

    const offers = [];
    for (const sv of (s['services'] ?? []) as { title?: string; serviceType?: string }[]) {
        offers.push({
            '@type': 'Offer',
            itemOffered: {
                '@type': 'Service',
                name: sv['title'] ?? '',
                serviceType: sv['serviceType'] ?? '',
            },
        });
    }

    const geo = (b['geo'] ?? {}) as Record<string, unknown>;
    graph.push({
        '@type': ['LocalBusiness', 'Carpenter', 'HomeAndConstructionBusiness'],
        '@id': siteAnchor('business'),
        name: b['name'] ?? '',
        image: abs(String(meta['ogImage'] ?? '')),
        url: siteAbs('/'),
        telephone: b['phoneSchema'] ?? '',
        faxNumber: b['faxSchema'] ?? '',
        email: b['email'] ?? '',
        priceRange: b['priceRange'] ?? '',
        parentOrganization: { '@id': siteAnchor('organization') },
        address: {
            '@type': 'PostalAddress',
            streetAddress: b['street'] ?? '',
            postalCode: b['postalCode'] ?? '',
            addressLocality: b['city'] ?? '',
            addressRegion: b['region'] ?? '',
            addressCountry: b['country'] ?? '',
        },
        geo: {
            '@type': 'GeoCoordinates',
            latitude: geo['lat'] ?? 0,
            longitude: geo['lng'] ?? 0,
        },
        hasMap: b['mapUrl'] ?? '',
        areaServed,
        openingHoursSpecification: hours,
        hasOfferCatalog: {
            '@type': 'OfferCatalog',
            name: 'Leistungen der ' + (b['name'] ?? ''),
            itemListElement: offers,
        },
    });

    graph.push({
        '@type': 'WebPage',
        '@id': siteAnchor('webpage'),
        url: siteAbs('/'),
        name: meta['title'] ?? '',
        isPartOf: { '@id': siteAnchor('website') },
        about: { '@id': siteAnchor('business') },
        inLanguage: 'de-DE',
        primaryImageOfPage: abs(String(meta['ogImage'] ?? '')),
    });

    graph.push({
        '@type': 'BreadcrumbList',
        '@id': siteAnchor('breadcrumb'),
        itemListElement: [{ '@type': 'ListItem', position: 1, name: 'Start', item: siteAbs('/') }],
    });

    const faq = [];
    for (const f of siteActiveFilter((s['faq'] ?? []) as { q?: string; a?: string }[])) {
        faq.push({
            '@type': 'Question',
            name: f['q'] ?? '',
            acceptedAnswer: { '@type': 'Answer', text: f['a'] ?? '' },
        });
    }
    if (faq.length > 0) {
        graph.push({
            '@type': 'FAQPage',
            '@id': siteAnchor('faq'),
            mainEntity: faq,
        });
    }

    return { '@context': 'https://schema.org', '@graph': graph };
}

/** Head-Infos für eine Seite ('' Start, 'impressum', 'datenschutz'). */
export function seoHeadInfo(s: SiteData, page = ''): {
    title: string;
    description: string;
    canonical: string;
} {
    const meta = (s['meta'] ?? {}) as Record<string, string>;
    const isLegal = page === 'impressum' || page === 'datenschutz';

    const title = isLegal
        ? siteGetStr(s, 'legal.' + page + '.title', meta['title'] ?? '')
        : siteGetStr(s, 'meta.title', '');
    const description = isLegal
        ? siteGetStr(s, 'legal.' + page + '.metaDescription', meta['description'] ?? '')
        : siteGetStr(s, 'meta.description', '');

    return { title, description, canonical: siteAbs(page === '' ? '/' : '/' + page + '/') };
}

/** JSON für das Structured-Data-Script (pretty). */
export function seoStructuredDataJson(s: SiteData): string {
    return JSON.stringify(seoStructuredData(s), null, 2);
}

/** LCP-Bild (Preload). */
export function seoLcpImage(s: SiteData): string {
    return siteGetStr(s, 'meta.lcpImage', siteAsset(s, 'content/kuechen/projekt-kueche.jpg'));
}