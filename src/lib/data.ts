/**
 * Zentraler Datenzugriff (Single Source of Truth).
 *
 * Port von php/data.php + php/config.php nach TypeScript für Astro:
 * Lädt data/site.json (mit tolstantem {config.KEY}-Resolve) und stellt
 * Hilfsfunktionen für das statische Rendering bereit. Alle editierbaren
 * Inhalte (Texte, Bilder, Slider, Galerie, SEO/Head-Infos) kommen aus
 * dieser Datei – identisch zur PHP-Variante.
 */
import { readFileSync } from 'node:fs';
import path from 'node:path';

// Root des Repos: Beim `astro build`/`astro dev` ist process.cwd() das
// Projektverzeichnis – import.meta.url würde im Build auf dist/ zeigen.
const repoRoot = process.cwd();

/** Liest eine JSON-Datei tolerant ein: entfernt führenden Block-Kommentar. */
function safeReadJson(filePath: string): Record<string, unknown> | null {
    try {
        const raw = readFileSync(filePath, 'utf8');
        let src = raw.trimStart();
        if (src.startsWith('/*')) {
            const end = src.indexOf('*/');
            if (end !== -1) {
                src = src.slice(end + 2).trimStart();
            }
        }
        const decoded: unknown = JSON.parse(src);
        return decoded !== null && typeof decoded === 'object' && !Array.isArray(decoded)
            ? (decoded as Record<string, unknown>)
            : null;
    } catch {
        return null;
    }
}

/** ZENTRALE PROJEKT-KONFIGURATION (Port von php/config.php project_config()). */
export function projectConfig(data: SiteData): Record<string, string> {
    const cfg = (data['config'] as Record<string, string>) ?? {};
    return {
        name: (cfg['name'] as string) ?? '',
        domain: (cfg['domain'] as string) ?? '',
        email: (cfg['email'] as string) ?? '',
        phone: (cfg['phone'] as string) ?? '',
        phoneSchema: (cfg['phoneSchema'] as string) ?? '',
        fax: (cfg['fax'] as string) ?? '',
        faxSchema: (cfg['faxSchema'] as string) ?? '',
        themeColor: (cfg['themeColor'] as string) ?? '#3D6490',
        assetsBase: (cfg['assetsBase'] as string) ?? '/assets',
        logo: (cfg['logo'] as string) ?? 'site/logo.png',
        port: (cfg['port'] as string) ?? '9999',
        adminPath: (cfg['adminPath'] as string) ?? '/admin',
    };
}

/**
 * Ersetzt "{config.KEY}"-Platzhalter in einem Wert rekursiv durch die Werte
 * des top-level "config"-Blocks aus site.json (Duplikat-Eliminierung).
 */
function resolveConfig(node: SiteData | unknown, cfg: Record<string, string>): void {
    if (node === null || typeof node !== 'object') {
        return;
    }
    for (const key of Object.keys(node as object)) {
        const v = (node as Record<string, unknown>)[key];
        if (v !== null && typeof v === 'object') {
            resolveConfig(v, cfg);
        } else if (typeof v === 'string' && v.includes('{config.')) {
            (node as Record<string, unknown>)[key] = v.replace(
                /\{config\.([a-zA-Z0-9_]+)\}/g,
                (_m, k: string) => cfg[k] ?? _m
            );
        }
    }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type SiteData = Record<string, any>;

let cached: SiteData | null = null;
let cachedCfg: Record<string, string> | null = null;

/** Site-Daten einmalig laden und liefern (identisches Verhalten zu site_load()). */
export function siteLoad(): SiteData {
    if (cached !== null) {
        return cached;
    }
    const siteFile = path.join(repoRoot, 'data', 'site.json');
    const data: SiteData = safeReadJson(siteFile) ?? {};

    // Projekt-Konfiguration als $data['config'] = { project: {...} }
    // (im PHP-Original aus php/config.php eingeblendet).
    data['config'] = { project: projectConfig(data) };

    // Werte aus dem config-Block in business/meta spiegeln.
    const cfg = data['config']['project'];
    const mirrors: Record<string, unknown> = {
        'business.name': cfg['name'],
        'business.phone': cfg['phone'],
        'business.email': cfg['email'],
        'meta.siteUrl': cfg['domain'],
    };
    for (const [p, val] of Object.entries(mirrors)) {
        if (val === null || val === undefined) {
            continue;
        }
        const parts = p.split('.');
        let ref = data;
        for (let i = 0; i < parts.length; i++) {
            const key = parts[i] as string;
            if (i === parts.length - 1) {
                ref[key] = val;
            } else {
                if (ref[key] === null || typeof ref[key] !== 'object') {
                    ref[key] = {};
                }
                ref = ref[key] as SiteData;
            }
        }
    }

    // {config.KEY}-Platzhalter überall auflösen.
    resolveConfig(data, cfg);

    cached = data;
    cachedCfg = cfg;
    return data;
}

/** Sicherer Pfad-Zugriff: siteGet(s, 'business.phone') */
export function siteGet(data: SiteData, path: string, def: unknown = null): unknown {
    let cur: unknown = data;
    for (const key of path.split('.')) {
        if (cur !== null && typeof cur === 'object' && !Array.isArray(cur)) {
            const obj = cur as Record<string, unknown>;
            if (Object.prototype.hasOwnProperty.call(obj, key)) {
                cur = obj[key];
                continue;
            }
        }
        return def;
    }
    return cur;
}

export function siteGetStr(data: SiteData, path: string, def = ''): string {
    const v = siteGet(data, path, def);
    return v === null || (typeof v !== 'string' && typeof v !== 'number' && typeof v !== 'boolean')
        ? def
        : String(v);
}

/** Escaped HTML (entspricht site_esc / htmlspecialchars). */
export function siteEsc(value: string): string {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/** Interne URL: externen URLs unverändert lassen, sonst root-absolut bearbeiten. */
export function siteAbs(path: string): string {
    if (path === '' || path.startsWith('http') || path.startsWith('//')) {
        return path;
    }
    return '/' + path.replace(/^\//, '');
}

/** Anker-ID-Pfad für interne Referenzen (z. B. "#organization") – root-absolut. */
export function siteAnchor(fragment: string): string {
    return '/#' + fragment.replace(/^#/, '');
}

/** Projekt-Konfiguration aus dem zentralen config-Block lesen. */
export function siteConfig(data: SiteData, p: string, def = ''): string {
    return siteGetStr(data, 'config.' + p, def);
}

/**
 * Zentrale Asset-Basis (z. B. /assets) mit Pfad kombinieren.
 * Domainfrei UND root-absolut (domainspezifische URLs bleiben unverändert).
 */
export function siteAsset(data: SiteData, p: string): string {
    if (p === '' || p.startsWith('http') || p.startsWith('//')) {
        return p;
    }
    const base = siteConfig(data, 'project.assetsBase', '/assets').replace(/\/$/, '');
    // Pfad enthält die Asset-Basis bereits (z. B. "/assets/content/...")?
    let full: string;
    if (base !== '' && (p === base || p.startsWith(base + '/'))) {
        full = p;
    } else {
        full = base + '/' + p.replace(/^\//, '');
    }
    return '/' + full.replace(/^\//, '');
}

/**
 * Filtert eine Liste von Einträgen auf aktive herab (Slider, Galerie, FAQ…).
 * Einträge ohne "active"-Feld gelten als aktiv.
 */
export function siteActiveFilter<T>(items: T[]): T[] {
    return items.filter(
        (item): item is T => item === null || typeof item !== 'object' || (item as { active?: unknown }).active !== false
    );
}

/** Aktuelle Projekt-Konfiguration (config-Block) als Interface. */
export interface ProjectConfig {
    name: string;
    domain: string;
    email: string;
    phone: string;
    phoneSchema: string;
    fax: string;
    faxSchema: string;
    themeColor: string;
    assetsBase: string;
    logo: string;
    port: string;
    adminPath: string;
}

/** Liefert die (einmalig aufgelöste) Projekt-Config. */
export function siteConfigProject(data: SiteData): ProjectConfig {
    return data['config']['project'];
}