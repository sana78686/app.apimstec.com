<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google OAuth (Search Console read-only)
    |--------------------------------------------------------------------------
    |
    | Create credentials in Google Cloud Console → APIs & Services → Credentials
    | → OAuth 2.0 Client ID (Web application). Enable "Google Search Console API"
    | for the same project. Authorized redirect URI must match GOOGLE_GSC_REDIRECT_URI.
    |
    */
    'client_id' => env('GOOGLE_CLIENT_ID'),

    'client_secret' => env('GOOGLE_CLIENT_SECRET'),

    /**
     * Single callback URL without CMS locale prefix, e.g.
     * https://app.example.com/oauth/google-search-console/callback
     */
    'gsc_redirect_uri' => env('GOOGLE_GSC_REDIRECT_URI'),

];
