/**
 * robots.txt (Port von robots.php) – benennt Sitemap und erlaubt KI-Crawler.
 */
import type { APIRoute } from 'astro';
import { siteLoad } from '../lib/data.ts';

export const GET: APIRoute = () => {
    const agents = [
        'GPTBot', 'ChatGPT-User', 'OAI-SearchBot', 'PerplexityBot', 'Google-Extended',
        'ClaudeBot', 'Claude-Web', 'Claude-Search', 'Anthropic-ai', 'Applebot-Extended',
        'Bytespider', 'CCBot', 'cohere-ai', 'Diffbot', 'ExaBot', 'FacebookBot',
        'Google-CloudVertexBot', 'GrokBot', 'Meta-ExternAgent', 'Mistral',
    ] as const;
    siteLoad(); // Konfiguration sicherstellen (Fehler wie im Original)
    let out = 'User-agent: *\nAllow: /\n\n';
    out += '# KI-/LLM-Crawler ausdrücklich erlaubt\n';
    for (const a of agents) {
        out += 'User-agent: ' + a + '\nAllow: /\n';
    }
    out += '\nSitemap: /sitemap.xml\n';
    return new Response(out, { headers: { 'Content-Type': 'text/plain; charset=utf-8' } });
};