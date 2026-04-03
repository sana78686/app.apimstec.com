<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\ContentManagerController;
use App\Http\Controllers\Controller;
use App\Support\ContentLocales;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomePageSeoController extends Controller
{
    public function index(Request $request): Response
    {
        $loc = ContentLocales::normalize(
            $request->query('content_locale') ?? $request->session()->get('cms_locale')
        );

        return Inertia::render('Seo/HomePageSeo/Index', [
            'contentLocale' => $loc,
            'metaTitle'      => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_META_TITLE, $loc),
            'metaDescription'=> ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_META_DESCRIPTION, $loc),
            'metaKeywords'   => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_META_KEYWORDS, $loc),
            'focusKeyword'   => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_FOCUS_KEYWORD, $loc),
            'ogTitle'        => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_OG_TITLE, $loc),
            'ogDescription'  => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_OG_DESCRIPTION, $loc),
            'ogImage'        => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_OG_IMAGE, $loc),
            'metaRobots'     => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_META_ROBOTS, $loc) ?: 'index,follow',
            'canonicalUrl'   => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_CANONICAL_URL, $loc),
            'frontendHeadSnippet' => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_FRONTEND_HEAD_SNIPPET, $loc),
            'flash'          => ['success' => session('success')],
        ]);
    }
}
