<?php

namespace App\Http\Controllers\Public;

use App\Domain\Platform\SeoAndStructuredDataService;
use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SeoAndContentController extends Controller
{
    public function sitemap(SeoAndStructuredDataService $seo): Response
    {
        return response($seo->generateSitemapXml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function robots(SeoAndStructuredDataService $seo): Response
    {
        return response($seo->generateRobotsTxt(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function showCmsPage(string $slug, SeoAndStructuredDataService $seo): View
    {
        $page = CmsPage::query()
            ->with('editor')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        $meta = $seo->resolveMetaForModel(
            model: $page,
            defaultTitle: "{$page->title} | BIN MALIK FITNESS",
            defaultDescription: $page->excerpt ?: substr(strip_tags($page->body), 0, 155),
            defaultCanonical: route('cms.pages.show', $page->slug),
        );

        $breadcrumbs = $seo->breadcrumbSchema([
            ['name' => 'Home', 'url' => route('home')],
            ['name' => $page->title, 'url' => route('cms.pages.show', $page->slug)],
        ]);

        $otherPages = CmsPage::query()
            ->where('status', 'published')
            ->whereKeyNot($page->id)
            ->orderBy('section')
            ->orderBy('title')
            ->get();

        return view('public.cms-page', [
            'page' => $page,
            'otherPages' => $otherPages,
            'seoMeta' => $meta,
            'jsonLdSchemas' => [$breadcrumbs],
        ]);
    }
}
