<?php

namespace App\Support;

use App\Http\Controllers\Seo\SchemaMarkupController;
use App\Models\Blog;
use App\Models\Domain;
use App\Models\Page;

/**
 * Builds JSON-LD @graph payloads for the public React sites (resolved per tenant Domain).
 */
final class PublicJsonLdBuilder
{
    public static function origin(Domain $domain): string
    {
        $o = $domain->publicSiteBaseUrl();

        return $o !== '' ? rtrim($o, '/') : '';
    }

    public static function stripHtml(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }
        $text = preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '';

        return trim($text);
    }

    /**
     * @param  array<string, mixed>  $homeRow  Fields from homeContent() before json_encode
     * @return array<string, mixed>|null
     */
    public static function homeGraph(Domain $domain, string $locale, array $homeRow): ?array
    {
        $origin = self::origin($domain);
        if ($origin === '') {
            return null;
        }

        $bcp47 = ContentLocales::normalize($locale);
        $brand = trim((string) ($homeRow['meta_title'] ?? ''));
        if ($brand === '') {
            $brand = trim((string) config('contact.public_site_name', '')) ?: 'Website';
        }
        $desc = trim((string) ($homeRow['meta_description'] ?? ''));
        $canonical = trim((string) ($homeRow['canonical_url'] ?? ''));
        if ($canonical === '') {
            $canonical = $origin.'/'.$bcp47.'/';
        }
        $logo = trim((string) ($homeRow['og_image'] ?? ''));
        if ($logo === '' || ! str_starts_with($logo, 'http')) {
            $logo = $origin.'/favicon.png';
        }

        $webPage = [
            '@type' => 'WebPage',
            '@id' => $canonical.'#webpage',
            'url' => $canonical,
            'name' => $brand,
            'inLanguage' => $bcp47,
            'isPartOf' => ['@id' => $origin.'/#website'],
            'about' => ['@id' => $origin.'/#organization'],
        ];
        if ($desc !== '') {
            $webPage['description'] = $desc;
        }

        $graph = [
            [
                '@type' => 'Organization',
                '@id' => $origin.'/#organization',
                'name' => $brand,
                'url' => $origin.'/',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $logo,
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => $origin.'/#website',
                'url' => $origin.'/',
                'name' => $brand,
                'inLanguage' => $bcp47,
                'publisher' => ['@id' => $origin.'/#organization'],
            ],
            $webPage,
        ];

        return [
            '@context' => 'https://schema.org',
            '@graph' => array_map(fn ($n) => self::dropNulls($n), $graph),
        ];
    }

    /**
     * PDF compressor tool: WebApplication + FAQPage + BreadcrumbList (FAQ text must match visible FAQ).
     *
     * @param  iterable<int, object{question: string, answer: string}>  $faqItems
     */
    public static function toolCompressGraph(Domain $domain, string $locale, iterable $faqItems, string $toolTitle = 'Compress PDF'): ?array
    {
        $origin = self::origin($domain);
        if ($origin === '') {
            return null;
        }

        $bcp47 = ContentLocales::normalize($locale);
        $toolUrl = $origin.'/'.$bcp47.'/compress';
        $graph = [];

        $graph[] = [
            '@type' => 'BreadcrumbList',
            '@id' => $toolUrl.'#breadcrumb',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => $origin.'/'.$bcp47.'/',
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $toolTitle,
                    'item' => $toolUrl,
                ],
            ],
        ];

        $graph[] = [
            '@type' => 'WebApplication',
            '@id' => $toolUrl.'#webapp',
            'name' => $toolTitle,
            'url' => $toolUrl,
            'applicationCategory' => 'UtilitiesApplication',
            'operatingSystem' => 'Any',
            'browserRequirements' => 'Requires JavaScript.',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
            ],
        ];

        $mainEntity = [];
        foreach ($faqItems as $row) {
            $q = isset($row->question) ? trim((string) $row->question) : '';
            $a = isset($row->answer) ? self::stripHtml((string) $row->answer) : '';
            if ($q === '' || $a === '') {
                continue;
            }
            $mainEntity[] = [
                '@type' => 'Question',
                'name' => $q,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $a,
                ],
            ];
        }

        if ($mainEntity !== []) {
            $graph[] = [
                '@type' => 'FAQPage',
                '@id' => $toolUrl.'#faq',
                'mainEntity' => $mainEntity,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }

    /**
     * @param  array<string, mixed>  $rewritten  canonical_url, og_image (absolute)
     */
    public static function pageGraph(Domain $domain, string $locale, Page $page, array $rewritten): ?array
    {
        $origin = self::origin($domain);
        if ($origin === '') {
            return null;
        }

        $bcp47 = ContentLocales::normalize($locale);
        $slug = (string) $page->slug;
        $pageUrl = $origin.'/'.$bcp47.'/page/'.$slug;
        $canonical = trim((string) ($rewritten['canonical_url'] ?? ''));
        if ($canonical === '') {
            $canonical = $pageUrl;
        }

        $graph = [];
        $graph[] = self::breadcrumbHomeAndCurrent($origin, $bcp47, $page->title ?? $slug, $canonical);

        $type = $page->schema_type ?? '';
        $schemaData = is_array($page->schema_data) ? $page->schema_data : [];

        $graph = array_merge($graph, self::typedNodesForContent(
            $type,
            $schemaData,
            $canonical,
            $page->title ?? '',
            self::stripHtml($page->meta_description ?? ''),
            $rewritten['og_image'] ?? null,
            null,
            null,
            $origin,
            $bcp47,
            false
        ));

        return self::wrapGraph($graph);
    }

    /**
     * @param  array<string, mixed>  $rewritten
     */
    public static function blogGraph(Domain $domain, string $locale, Blog $blog, array $rewritten): ?array
    {
        $origin = self::origin($domain);
        if ($origin === '') {
            return null;
        }

        $bcp47 = ContentLocales::normalize($locale);
        $slug = (string) $blog->slug;
        $postUrl = $origin.'/'.$bcp47.'/blog/'.$slug;
        $canonical = trim((string) ($rewritten['canonical_url'] ?? ''));
        if ($canonical === '') {
            $canonical = $postUrl;
        }

        $graph = [];
        $graph[] = self::breadcrumbHomeAndCurrent($origin, $bcp47, $blog->title ?? $slug, $canonical);

        $type = $blog->schema_type ?? '';
        $schemaData = is_array($blog->schema_data) ? $blog->schema_data : [];

        $published = $blog->published_at?->toIso8601String();
        $modified = $blog->updated_at?->toIso8601String();
        $authorName = $blog->relationLoaded('author') && $blog->author
            ? trim((string) $blog->author->name)
            : null;

        $graph = array_merge($graph, self::typedNodesForContent(
            $type,
            $schemaData,
            $canonical,
            $blog->title ?? '',
            self::stripHtml($blog->meta_description ?? $blog->excerpt ?? ''),
            $rewritten['og_image'] ?? null,
            $published,
            $modified,
            $origin,
            $bcp47,
            true,
            $authorName
        ));

        return self::wrapGraph($graph);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function typedNodesForContent(
        string $type,
        array $schemaData,
        string $canonical,
        string $headline,
        string $description,
        ?string $imageUrl,
        ?string $datePublished,
        ?string $dateModified,
        string $origin,
        string $bcp47,
        bool $isBlog,
        ?string $authorName = null
    ): array {
        $out = [];
        $headline = trim($headline) !== '' ? trim($headline) : 'Page';
        $imageUrl = $imageUrl && str_starts_with($imageUrl, 'http') ? $imageUrl : null;

        if ($type === SchemaMarkupController::SCHEMA_ARTICLE || (($type === '' || $type === SchemaMarkupController::SCHEMA_NONE) && $isBlog)) {
            $article = [
                '@type' => 'Article',
                '@id' => $canonical.'#article',
                'headline' => $headline,
                'url' => $canonical,
                'inLanguage' => $bcp47,
                'isPartOf' => ['@id' => $origin.'/#website'],
            ];
            if ($description !== '') {
                $article['description'] = $description;
            }
            if ($imageUrl) {
                $article['image'] = [$imageUrl];
            }
            if ($datePublished) {
                $article['datePublished'] = $datePublished;
            }
            if ($dateModified) {
                $article['dateModified'] = $dateModified;
            }
            if ($authorName) {
                $article['author'] = [
                    '@type' => 'Person',
                    'name' => $authorName,
                ];
            }
            $article['publisher'] = ['@id' => $origin.'/#organization'];
            $article['mainEntityOfPage'] = ['@id' => $canonical.'#webpage'];
            $out[] = $article;
            $out[] = [
                '@type' => 'WebPage',
                '@id' => $canonical.'#webpage',
                'url' => $canonical,
                'name' => $headline,
                'inLanguage' => $bcp47,
                'isPartOf' => ['@id' => $origin.'/#website'],
            ];
        }

        if ($type === SchemaMarkupController::SCHEMA_FAQ) {
            $faqBlock = self::faqMainEntityFromSchemaData($schemaData);
            if ($faqBlock !== []) {
                $out[] = [
                    '@type' => 'FAQPage',
                    '@id' => $canonical.'#faq',
                    'mainEntity' => $faqBlock,
                ];
            }
        }

        if ($type === SchemaMarkupController::SCHEMA_PRODUCT) {
            $name = trim((string) ($schemaData['name'] ?? $headline));
            $out[] = [
                '@type' => 'Product',
                '@id' => $canonical.'#product',
                'name' => $name,
                'description' => trim((string) ($schemaData['description'] ?? $description)) ?: null,
                'offers' => [
                    '@type' => 'Offer',
                    'price' => (string) ($schemaData['price'] ?? '0'),
                    'priceCurrency' => (string) ($schemaData['priceCurrency'] ?? 'USD'),
                ],
            ];
        }

        if ($type === '' || $type === SchemaMarkupController::SCHEMA_NONE || $type === SchemaMarkupController::SCHEMA_BREADCRUMB) {
            if (! isset($out[0])) {
                $wp = [
                    '@type' => 'WebPage',
                    '@id' => $canonical.'#webpage',
                    'url' => $canonical,
                    'name' => $headline,
                    'inLanguage' => $bcp47,
                    'isPartOf' => ['@id' => $origin.'/#website'],
                ];
                if ($description !== '') {
                    $wp['description'] = $description;
                }
                if (! $isBlog || $type === SchemaMarkupController::SCHEMA_BREADCRUMB) {
                    $out[] = $wp;
                }
            }
        }

        /** Article/Blog + extra FAQ in schema_data (Schema type “Article”, questions in CMS JSON). */
        if ($type !== SchemaMarkupController::SCHEMA_FAQ) {
            $comboFaq = self::faqMainEntityFromSchemaData($schemaData);
            if ($comboFaq !== [] && (
                $type === SchemaMarkupController::SCHEMA_ARTICLE
                || (($type === '' || $type === SchemaMarkupController::SCHEMA_NONE) && $isBlog)
            )) {
                $out[] = [
                    '@type' => 'FAQPage',
                    '@id' => $canonical.'#faq-extra',
                    'mainEntity' => $comboFaq,
                ];
            }
        }

        return array_values(array_filter($out, fn ($x) => $x !== null && $x !== []));
    }

    /**
     * Optional schema_data.faq_questions: list of { "question": "...", "answer": "html or text" }
     *
     * @return list<array<string, mixed>>
     */
    private static function faqMainEntityFromSchemaData(array $schemaData): array
    {
        $raw = $schemaData['faq_questions'] ?? $schemaData['questions'] ?? null;
        if (! is_array($raw)) {
            return [];
        }
        $main = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $q = trim((string) ($row['question'] ?? $row['name'] ?? ''));
            $a = self::stripHtml((string) ($row['answer'] ?? $row['text'] ?? ''));
            if ($q === '' || $a === '') {
                continue;
            }
            $main[] = [
                '@type' => 'Question',
                'name' => $q,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $a,
                ],
            ];
        }

        return $main;
    }

    /**
     * @return array<string, mixed>
     */
    private static function breadcrumbHomeAndCurrent(
        string $origin,
        string $bcp47,
        string $title,
        string $canonical
    ): array {
        return [
            '@type' => 'BreadcrumbList',
            '@id' => $canonical.'#breadcrumb',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => $origin.'/'.$bcp47.'/',
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $title,
                    'item' => $canonical,
                ],
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return array<string, mixed>|null
     */
    private static function wrapGraph(array $nodes): ?array
    {
        $clean = [];
        foreach ($nodes as $n) {
            if (! is_array($n)) {
                continue;
            }
            $clean[] = self::dropNulls($n);
        }
        if ($clean === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $clean,
        ];
    }

    /**
     * @param  array<string, mixed>  $a
     * @return array<string, mixed>
     */
    private static function dropNulls(array $a): array
    {
        $o = [];
        foreach ($a as $k => $v) {
            if ($v === null) {
                continue;
            }
            if (is_array($v)) {
                $v = self::dropNulls($v);
                if ($v === []) {
                    continue;
                }
            }
            $o[$k] = $v;
        }

        return $o;
    }
}
