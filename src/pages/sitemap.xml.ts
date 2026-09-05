/**
 * sitemap.xml (Port von sitemap.php) – liefert die URL-Liste aus site.json.
 */
import type { APIRoute } from 'astro';
import { siteLoad, siteGetStr, siteEsc } from '../lib/data.ts';

export const GET: APIRoute = () => {
    const s = siteLoad();
    // domainfrei wie im Original (site_base): Seiten liegen unter '/'
    const base = '';
    const pages = [
        { loc: base + '/', priority: '1.0', changefreq: 'monthly' },
        { loc: base + '/impressum/', priority: '0.3', changefreq: 'yearly' },
        { loc: base + '/datenschutz/', priority: '0.3', changefreq: 'yearly' },
    ];
    const lastmod = new Date().toISOString().slice(0, 10);

    let out = '<?xml version="1.0" encoding="UTF-8"?>\n';
    out += '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n';
    for (const p of pages) {
        out += '  <url>\n';
        out += '    <loc>' + siteEsc(p.loc) + "</loc>\n";
        out += '    <lastmod>' + lastmod + "</lastmod>\n";
        out += '    <changefreq>' + p.changefreq + "</changefreq>\n";
        out += '    <priority>' + p.priority + "</priority>\n";
        out += "  </url>\n";
    }
    out += '</urlset>\n';
    return new Response(out, { headers: { 'Content-Type': 'application/xml; charset=utf-8' } });
};