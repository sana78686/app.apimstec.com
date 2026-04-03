<?php

return [

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
