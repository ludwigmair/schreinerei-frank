<?php
/**
 * robots.txt (dynamisch aus site.json) – wird über .htaccess als robots.txt
 * ausgeliefert. Zeigt die Sitemap automatisch an und erlaubt KI-Crawler.
 */
declare(strict_types=1);

require_once __DIR__ . '/php/data.php';

$s = site_load();
$base = site_base($s);

header('Content-Type: text/plain; charset=utf-8');

$agents = [
    'GPTBot', 'ChatGPT-User', 'OAI-SearchBot', 'PerplexityBot', 'Google-Extended',
    'ClaudeBot', 'Claude-Web', 'Claude-Search', 'Anthropic-ai', 'Applebot-Extended',
    'Bytespider', 'CCBot', 'cohere-ai', 'Diffbot', 'ExaBot', 'FacebookBot',
    'Google-CloudVertexBot', 'GrokBot', 'Meta-ExternAgent', 'Mistral',
];

$out = "User-agent: *\nAllow: /\n\n";
$out .= "# KI-/LLM-Crawler ausdrücklich erlaubt\n";
foreach ($agents as $a) {
    $out .= "User-agent: " . $a . "\nAllow: /\n";
}
$out .= "\nSitemap: " . $base . "/sitemap.xml\n";

echo $out;
