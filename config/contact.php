<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public contact form (React → POST /api/public/contact/send)
    |--------------------------------------------------------------------------
    */

    'form_mail_to' => env('CONTACT_FORM_MAIL_TO', 'apimstecofficial@gmail.com'),

    /** Shown in email subject/body (e.g. CompressPDF, compresspdf.id). */
    'public_site_name' => env('CONTACT_FORM_SITE_NAME', env('APP_NAME', 'Compress PDF')),
];
