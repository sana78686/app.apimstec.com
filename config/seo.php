<?php

$cmsPublicLocales = array_values(array_filter(array_map(
    static fn (string $s): string => strtolower(trim($s)),
    explode(',', (string) env('CMS_PUBLIC_LOCALES', 'id,en'))
)));

return [

    /*
    |--------------------------------------------------------------------------
    | Public site locales (CMS list filters: Pages, Blogs, Sitemap, Sections)
    |--------------------------------------------------------------------------
    |
    | Comma-separated locale codes that match ContentLocales::SUPPORTED.
    | Default id,en matches compresspdf.id; set e.g. "id,en,ms" to add more.
    |
    */
    'public_locales' => $cmsPublicLocales !== [] ? $cmsPublicLocales : ['id', 'en'],

    /*
    |--------------------------------------------------------------------------
    | Sitemap / robots.txt — resolve tenant when Domain.is_active is false
    |--------------------------------------------------------------------------
    |
    | When true, public requests for sitemap.xml and robots.txt (Host header or
    | /{domain}/sitemap.xml path) can still resolve a domain row that is marked
    | inactive in the CMS. CMS session switching still requires is_active = true.
    |
    */
    'allow_inactive_domains_for_sitemap_robots' => env('SEO_ALLOW_INACTIVE_FOR_SITEMAP_ROBOTS', true),

];
