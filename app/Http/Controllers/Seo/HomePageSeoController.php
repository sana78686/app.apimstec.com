<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\ContentManagerController;
use App\Http\Controllers\Controller;
use App\Models\ContentManagerSetting;
use Inertia\Inertia;
use Inertia\Response;

class HomePageSeoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Seo/HomePageSeo/Index', [
            'metaTitle'      => ContentManagerSetting::get(ContentManagerController::KEY_HOME_META_TITLE, ''),
            'metaDescription'=> ContentManagerSetting::get(ContentManagerController::KEY_HOME_META_DESCRIPTION, ''),
            'metaKeywords'   => ContentManagerSetting::get(ContentManagerController::KEY_HOME_META_KEYWORDS, ''),
            'focusKeyword'   => ContentManagerSetting::get(ContentManagerController::KEY_HOME_FOCUS_KEYWORD, ''),
            'ogTitle'        => ContentManagerSetting::get(ContentManagerController::KEY_HOME_OG_TITLE, ''),
            'ogDescription'  => ContentManagerSetting::get(ContentManagerController::KEY_HOME_OG_DESCRIPTION, ''),
            'ogImage'        => ContentManagerSetting::get(ContentManagerController::KEY_HOME_OG_IMAGE, ''),
            'metaRobots'     => ContentManagerSetting::get(ContentManagerController::KEY_HOME_META_ROBOTS, 'index,follow'),
            'canonicalUrl'   => ContentManagerSetting::get(ContentManagerController::KEY_HOME_CANONICAL_URL, ''),
            'flash'          => ['success' => session('success')],
        ]);
    }
}
