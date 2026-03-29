<?php

namespace App\Http\Controllers;

use App\Models\RobotsTxt;
use Illuminate\Http\Response;

class RobotsTxtController extends Controller
{
    /**
     * Serve robots.txt for crawlers. Content is from Robots.txt Manager; includes sitemap link if missing.
     */
    public function __invoke(): Response
    {
        $content = RobotsTxt::getContent();

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
