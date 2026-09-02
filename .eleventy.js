const fs = require("fs");
const path = require("path");

/** JSON-LD @graph aus content/site.json bauen – so wandern CMS-Änderungen
 *  automatisch in die strukturierten Daten. */
function buildStructuredData(s) {
  const base = s.meta.siteUrl.replace(/\/$/, "");
  const abs = (p) => (p && p.startsWith("http") ? p : base + p);
  const b = s.business;

  return {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": "Organization",
        "@id": base + "/#organization",
        name: b.name,
        url: base + "/",
        logo: base + "/assets/img/logo-schreinerei-frank.png",
        email: b.email,
        telephone: b.phoneSchema,
        founder: { "@type": "Person", name: b.owner },
        address: {
          "@type": "PostalAddress",
          streetAddress: b.street,
          postalCode: b.postalCode,
          addressLocality: b.city,
          addressRegion: b.region,
          addressCountry: b.country,
        },
      },
      {
        "@type": "WebSite",
        "@id": base + "/#website",
        url: base + "/",
        name: b.name,
        inLanguage: "de-DE",
        publisher: { "@id": base + "/#organization" },
      },
      {
        "@type": ["LocalBusiness", "Carpenter", "HomeAndConstructionBusiness"],
        "@id": base + "/#business",
        name: b.name,
        image: abs(s.meta.ogImage),
        url: base + "/",
        telephone: b.phoneSchema,
        faxNumber: b.faxSchema,
        email: b.email,
        priceRange: b.priceRange,
        parentOrganization: { "@id": base + "/#organization" },
        address: {
          "@type": "PostalAddress",
          streetAddress: b.street,
          postalCode: b.postalCode,
          addressLocality: b.city,
          addressRegion: b.region,
          addressCountry: b.country,
        },
        geo: { "@type": "GeoCoordinates", latitude: b.geo.lat, longitude: b.geo.lng },
        hasMap: b.mapUrl,
        areaServed: b.areaServed.map((item) => ({ "@type": "Place", name: typeof item === "string" ? item : item.ort })),
        openingHoursSpecification: b.openingHoursSpec.map((o) => ({
          "@type": "OpeningHoursSpecification",
          dayOfWeek: o.days,
          opens: o.opens,
          closes: o.closes,
        })),
        hasOfferCatalog: {
          "@type": "OfferCatalog",
          name: "Leistungen der " + b.name,
          itemListElement: s.services.map((sv) => ({
            "@type": "Offer",
            itemOffered: { "@type": "Service", name: sv.title, serviceType: sv.serviceType },
          })),
        },
      },
      {
        "@type": "WebPage",
        "@id": base + "/#webpage",
        url: base + "/",
        name: s.meta.title,
        isPartOf: { "@id": base + "/#website" },
        about: { "@id": base + "/#business" },
        inLanguage: "de-DE",
        primaryImageOfPage: abs(s.meta.ogImage),
      },
      {
        "@type": "BreadcrumbList",
        "@id": base + "/#breadcrumb",
        itemListElement: [
          { "@type": "ListItem", position: 1, name: "Start", item: base + "/" },
        ],
      },
      {
        "@type": "FAQPage",
        "@id": base + "/#faq",
        mainEntity: s.faq.map((f) => ({
          "@type": "Question",
          name: f.q,
          acceptedAnswer: { "@type": "Answer", text: f.a },
        })),
      },
    ],
  };
}

module.exports = function (eleventyConfig) {
  // admin/ nur als statische Kopie behandeln, nicht als Template rendern
  eleventyConfig.ignores.add("src/admin/**");

  // Nunjucks: Objekt/Wert als JSON-String ausgeben (für data-* Attribute u. Ä.)
  eleventyConfig.addFilter("json", function (value) {
    return JSON.stringify(value);
  });

  // Aus /pfad/bild.jpg → /pfad/bild.webp (nur bei rasterformaten; sonst unverändert)
  eleventyConfig.addFilter("toWebp", function (src) {
    if (typeof src !== "string") return src;
    return src.replace(/\.(jpe?g|png)$/i, ".webp");
  });

  // Statische Dateien 1:1 kopieren
  eleventyConfig.addPassthroughCopy({ "src/assets": "assets" });
  eleventyConfig.addPassthroughCopy({ "src/admin": "admin" });
  eleventyConfig.addPassthroughCopy({ "src/robots.txt": "robots.txt" });
  eleventyConfig.addPassthroughCopy({ "src/sitemap.xml": "sitemap.xml" });
  eleventyConfig.addPassthroughCopy({ "src/site.webmanifest": "site.webmanifest" });
  eleventyConfig.addPassthroughCopy({ "src/llms.txt": "llms.txt" });

  // Bei Änderungen an den Inhalten neu bauen (CMS schreibt hierhin)
  eleventyConfig.addWatchTarget("./content/");

  // JSON-LD als globale Daten, frisch bei jedem Build
  eleventyConfig.addGlobalData("structuredData", () => {
    const raw = fs.readFileSync(path.join(__dirname, "content/site.json"), "utf8");
    return buildStructuredData(JSON.parse(raw));
  });

  return {
    dir: {
      input: "src",
      output: "_site",
      includes: "_includes",
      data: "../content", // -> content/site.json wird als {{ site }} verfügbar
    },
    htmlTemplateEngine: "njk",
    markdownTemplateEngine: "njk",
  };
};
