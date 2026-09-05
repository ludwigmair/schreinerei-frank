/**
 * site.webmanifest (Port von webmanifest.php) – Web-App-Manifest aus site.json.
 */
import type { APIRoute } from 'astro';
import { siteLoad, siteConfig } from '../lib/data.ts';

export const GET: APIRoute = () => {
    const s = siteLoad();
    const name = (s['business']?.['name'] as string) ?? 'Schreinerei Frank';
    const assets = siteConfig(s, 'project.assetsBase', '/assets');
    const description = (s['meta']?.['description'] as string) ?? 'Meisterschreinerei in Seeon im Chiemgau – Küchen, Treppen, Türen und Möbel aus Massivholz.';

    const manifest = {
        name,
        short_name: name,
        description,
        lang: 'de',
        start_url: '/',
        scope: '/',
        display: 'browser',
        theme_color: siteConfig(s, 'project.themeColor', '#3D6490'),
        background_color: '#FFFFFF',
        icons: [
            { src: '/favicon.ico', type: 'image/x-icon', sizes: 'any' },
            { src: assets + '/site/icon-192.png', type: 'image/png', sizes: '192x192' },
            { src: assets + '/site/icon-512.png', type: 'image/png', sizes: '512x512' },
        ],
    };
    return new Response(JSON.stringify(manifest, null, 4) + '\n', {
        headers: { 'Content-Type': 'application/manifest+json; charset=utf-8' },
    });
};