import { defineConfig } from 'astro/config';

// https://astro.build/config
export default defineConfig({
    // Test-Subdomain während der Migration; für Produktion später auf die
    // echte Domain zu setzen (schreinerei-frank.typopublic.com).
    site: 'https://schreinerei-frank-astro.typopublic.com/',
    output: 'static',
    build: {
        // Assets (Bilder/CSS) liegen in public/assets/ und werden 1:1 kopiert.
        assets: 'assets',
    },
});