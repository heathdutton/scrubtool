<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scrubtool Theme Variables
    |--------------------------------------------------------------------------
    |
    | Optionally deploy additional JS such as analytics via Google Tag Manager.
    | Optionally provide an external CSS stylesheet to load via https.
    |
    */
    'google_tag_manager' => env('THEME_GTM', 'GTM-XXXXXXX'),
    'external_css'       => env('THEME_EXT_CSS'),
    'repo'               => env('THEME_REPO'),
    'repo_link'          => env('THEME_REPO_LINK'),
    'copyright'          => env('THEME_COPYRIGHT'),
    'copyright_link'     => env('THEME_COPYRIGHT_LINK'),

];
