/**
 * llms.txt (Port von llms.php) – kompakter KI-/LLM-Überblick aus site.json.
 */
import type { APIRoute } from 'astro';
import { type SiteData, siteGetStr, siteLoad } from '../lib/data.ts';

const sget = (s: SiteData, path: string, def = ''): string => siteGetStr(s, path, def);

export const GET: APIRoute = () => {
    const s = siteLoad() as SiteData;
    const b = (s['business'] ?? {}) as Record<string, unknown>;
    const meta = (s['meta'] ?? {}) as Record<string, unknown>;
    const ui = ((s['ui'] ?? {})['llms'] ?? {}) as Record<string, unknown>;
    const services = (s['services'] ?? []) as Record<string, unknown>[];
    const about = (s['about'] ?? {}) as Record<string, unknown>;
    const areaServed = (b['areaServed'] ?? []) as unknown[];

    let out = '';
    out += '# ' + sget(s, 'business.name', 'Schreinerei Frank') + '\n\n';
    out += '> ' + sget(s, 'meta.description', '') + '\n\n';
    out += '## ' + sget(s, 'ui.llms.headingServices', 'Leistungen') + '\n';
    for (const sv of services) {
        out += '- ' + siteGetStr(sv, 'title', '') + ': ' + siteGetStr(sv, 'text', '') + '\n';
    }
    out += '\n## ' + sget(s, 'ui.llms.headingApproach', 'Arbeitsweise') + '\n';
    out += '- ' + siteGetStr(about, 'text', '') + '\n';
    for (const pt of (about['points'] ?? []) as unknown[]) {
        out += '- ' + String(pt ?? '') + '\n';
    }
    out += '\n## ' + sget(s, 'ui.llms.headingServiceArea', 'Einzugsgebiet') + '\n';
    if (areaServed.length > 0) {
        out += '- ' + areaServed.join(', ') + ' ' + siteGetStr(b, 'areaServedNote', '') + '\n';
    }
    out += '\n## ' + sget(s, 'ui.llms.headingContact', 'Kontakt') + '\n';
    out += '- ' + siteGetStr(ui, 'labelAddress', 'Adresse:') + ' ' + siteGetStr(b, 'street', '') + ', ' + siteGetStr(b, 'postalCode', '') + ' ' + siteGetStr(b, 'city', '') + ', ' + siteGetStr(b, 'country', '') + '\n';
    out += '- ' + siteGetStr(ui, 'labelPhone', 'Telefon:') + ' ' + siteGetStr(b, 'phone', '') + '\n';
    out += '- ' + siteGetStr(ui, 'labelEmail', 'E-Mail:') + ' ' + siteGetStr(b, 'email', '') + '\n';
    out += '- ' + siteGetStr(ui, 'labelHours', 'Öffnungszeiten:') + ' ' + siteGetStr(b, 'openingHoursText', '') + '\n';
    out += '- ' + siteGetStr(ui, 'labelWebsite', 'Website:') + ' /';

    return new Response(out + '\n', { headers: { 'Content-Type': 'text/plain; charset=utf-8' } });
};