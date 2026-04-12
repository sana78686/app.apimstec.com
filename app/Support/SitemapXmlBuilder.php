<?php

namespace App\Support;

use App\Models\Domain;

/**
 * Builds sitemap XML (same output as {@see \App\Http\Controllers\SitemapController}).
 */
final class SitemapXmlBuilder
{
    public static function forDomain(Domain $domain): string
    {
        $urls = SitemapUrlCollector::forDomain($domain);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>'."\n";
            $xml .= '    <lastmod>'.htmlspecialchars($u['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</lastmod>'."\n";
            $xml .= '    <changefreq>'.htmlspecialchars($u['changefreq'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</changefreq>'."\n";
            $xml .= '    <priority>'.htmlspecialchars($u['priority'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</priority>'."\n";
            $xml .= '  </url>'."\n";
        }
        $xml .= '</urlset>';

        return $xml;
    }
}
