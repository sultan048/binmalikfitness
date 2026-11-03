<?php

namespace App\Domain\Platform;

use App\Models\CmsPage;
use App\Models\CoachingPackage;
use App\Models\DietPlan;
use App\Models\Faq;
use App\Models\OpeningHour;
use App\Models\Product;
use App\Models\SeoMetadata;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SeoAndStructuredDataService
{
    /**
     * Resolves SEO metadata for an Eloquent entity from `seo_metadata` table, or falls back to clean defaults.
     *
     * @return array{title: string, description: string, canonical: string, og_image: string, robots: string}
     */
    public function resolveMetaForModel(
        ?Model $model,
        string $defaultTitle,
        string $defaultDescription,
        ?string $defaultCanonical = null,
        ?string $defaultOgImage = null,
        string $defaultRobots = 'index,follow'
    ): array {
        $seo = null;
        if ($model !== null && $model->getKey() !== null) {
            $seo = SeoMetadata::query()
                ->where('seoable_type', $model::class)
                ->where('seoable_id', $model->getKey())
                ->first();
        }

        $title = trim((string) ($seo?->meta_title ?: $seo?->title ?: $defaultTitle));
        $description = trim((string) ($seo?->meta_description ?: $seo?->description ?: $defaultDescription));
        $canonical = trim((string) ($seo?->canonical_url ?: $defaultCanonical ?: url()->current()));
        $ogImage = trim((string) ($seo?->og_image ?: $defaultOgImage ?: url('/images/demo/hero-training.png')));
        $robots = trim((string) ($seo?->robots ?: $defaultRobots));

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'og_image' => $ogImage,
            'robots' => $robots,
        ];
    }

    /**
     * Builds Organization + HealthClub JSON-LD strictly from database settings & opening hours.
     *
     * @return array<string, mixed>
     */
    public function organizationAndHealthClubSchema(): array
    {
        $city = (string) Setting::getValue('gym.city', 'Pakistan (City Pending Owner Confirmation)');
        $address = (string) Setting::getValue('gym.address', 'Facility Address Pending Owner Confirmation');
        $phone = (string) Setting::getValue('gym.phone', '+92 300 0000000');

        $openingSpecs = [];
        $hours = OpeningHour::orderBy('day_of_week')->get();
        foreach ($hours as $hour) {
            if (! $hour->is_closed && $hour->opens_at && $hour->closes_at) {
                $openingSpecs[] = [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => $hour->day_name,
                    'opens' => substr((string) $hour->opens_at, 0, 5),
                    'closes' => substr((string) $hour->closes_at, 0, 5),
                ];
            }
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => url('/').'#organization',
                    'name' => 'BIN MALIK FITNESS',
                    'url' => url('/'),
                    'logo' => url('/images/demo/hero-training.png'),
                    'areaServed' => [
                        '@type' => 'Country',
                        'name' => 'Pakistan',
                    ],
                ],
                [
                    '@type' => 'HealthClub',
                    '@id' => url('/gym').'#gym',
                    'name' => 'BIN MALIK FITNESS — Strength & Conditioning Facility',
                    'url' => url('/gym'),
                    'telephone' => $phone,
                    'priceRange' => 'PKR',
                    'address' => [
                        '@type' => 'PostalAddress',
                        'streetAddress' => $address,
                        'addressLocality' => $city,
                        'addressCountry' => 'PK',
                    ],
                    'openingHoursSpecification' => $openingSpecs,
                ],
            ],
        ];
    }

    /**
     * Builds FAQPage JSON-LD strictly from published Faq database records.
     *
     * @param  Collection<int, Faq>  $faqs
     * @return array<string, mixed>
     */
    public function faqPageSchema(Collection $faqs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqs->map(fn (Faq $faq) => [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq->answer,
                ],
            ])->values()->all(),
        ];
    }

    /**
     * Builds Product + Offer JSON-LD from real Product, ProductVariant, and approved ProductReview records.
     * Never emits AggregateRating unless approved verified-purchase reviews exist in the database.
     *
     * @return array<string, mixed>
     */
    public function productSchema(Product $product): array
    {
        $product->loadMissing(['brand', 'defaultVariant', 'variants', 'approvedReviews.user', 'primaryImage']);
        $variant = $product->defaultVariant ?? $product->variants->first();
        $variantPricePaisa = (int) ($variant?->price ?? $variant?->price_paisa ?? 0);
        $pricePkr = number_format($variantPricePaisa / 100, 2, '.', '');
        $inStock = $product->variants->sum(fn ($v) => $v->availableStock()) > 0;

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'sku' => $product->sku,
            'description' => $product->short_description ?: $product->subtitle ?: $product->name,
            'image' => [url($product->primaryImage?->path ?? '/images/demo/supplement-whey.png')],
            'brand' => [
                '@type' => 'Brand',
                'name' => $product->brand?->name ?? 'BIN MALIK FITNESS',
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => route('products.show', $product->slug),
                'priceCurrency' => 'PKR',
                'price' => $pricePkr,
                'availability' => $inStock
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
            ],
        ];

        $approvedReviews = $product->approvedReviews;
        if ($approvedReviews->isNotEmpty()) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round((float) $approvedReviews->avg('rating'), 1),
                'reviewCount' => $approvedReviews->count(),
                'bestRating' => 5,
                'worstRating' => 1,
            ];

            $schema['review'] = $approvedReviews->take(5)->map(fn ($rev) => [
                '@type' => 'Review',
                'author' => [
                    '@type' => 'Person',
                    'name' => $rev->user?->name ?? 'Verified Buyer',
                ],
                'datePublished' => $rev->created_at?->toDateString() ?? now()->toDateString(),
                'name' => $rev->title,
                'reviewBody' => $rev->body,
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => $rev->rating,
                    'bestRating' => 5,
                    'worstRating' => 1,
                ],
            ])->values()->all();
        }

        return $schema;
    }

    /**
     * Builds Service JSON-LD for an Online Coaching Package.
     *
     * @return array<string, mixed>
     */
    public function coachingServiceSchema(CoachingPackage $package): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $package->name,
            'serviceType' => '1-on-1 Online Fitness & Nutrition Coaching',
            'description' => $package->tagline ?: $package->who_is_it_for ?: $package->name,
            'provider' => [
                '@type' => 'Organization',
                'name' => 'BIN MALIK FITNESS',
                'url' => url('/'),
            ],
            'areaServed' => [
                '@type' => 'Country',
                'name' => 'Pakistan',
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => route('coaching.show', $package->slug),
                'priceCurrency' => 'PKR',
                'price' => number_format($package->price / 100, 2, '.', ''),
                'availability' => 'https://schema.org/InStock',
            ],
        ];
    }

    /**
     * Builds Course / Digital Program JSON-LD for a Diet Plan.
     *
     * @return array<string, mixed>
     */
    public function dietPlanSchema(DietPlan $plan): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $plan->title,
            'description' => $plan->summary,
            'provider' => [
                '@type' => 'Organization',
                'name' => 'BIN MALIK FITNESS',
                'sameAs' => url('/'),
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => route('diet-plans.show', $plan->slug),
                'priceCurrency' => 'PKR',
                'price' => number_format($plan->price / 100, 2, '.', ''),
                'category' => $plan->category,
            ],
        ];
    }

    /**
     * Builds BreadcrumbList JSON-LD from an ordered list of ['name' => ..., 'url' => ...] items.
     *
     * @param  list<array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public function breadcrumbSchema(array $items): array
    {
        $listItems = [];
        foreach (array_values($items) as $index => $item) {
            $listItems[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $listItems,
        ];
    }

    /**
     * Generates XML Sitemap strictly for indexable public pages.
     * Never exposes /manage, /account, /cart, /orders, /login, or noindex entities.
     */
    public function generateSitemapXml(): string
    {
        $noindexKeys = SeoMetadata::query()
            ->where('robots', 'like', '%noindex%')
            ->get()
            ->map(fn (SeoMetadata $s) => $s->seoable_type.':'.$s->seoable_id)
            ->flip();

        $urls = [
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily', 'lastmod' => now()->toAtomString()],
            ['loc' => route('coaching.index'), 'priority' => '0.9', 'changefreq' => 'weekly', 'lastmod' => now()->toAtomString()],
            ['loc' => route('shop.index'), 'priority' => '0.9', 'changefreq' => 'daily', 'lastmod' => now()->toAtomString()],
            ['loc' => route('gym.index'), 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => now()->toAtomString()],
        ];

        foreach (CoachingPackage::where('status', 'active')->orderBy('sort_order')->get() as $pkg) {
            if (! $noindexKeys->has(CoachingPackage::class.':'.$pkg->id)) {
                $urls[] = [
                    'loc' => route('coaching.show', $pkg->slug),
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                    'lastmod' => ($pkg->updated_at ?? now())->toAtomString(),
                ];
            }
        }

        foreach (DietPlan::where('status', 'active')->orderBy('id')->get() as $diet) {
            if (! $noindexKeys->has(DietPlan::class.':'.$diet->id)) {
                $urls[] = [
                    'loc' => route('diet-plans.show', $diet->slug),
                    'priority' => '0.8',
                    'changefreq' => 'weekly',
                    'lastmod' => ($diet->updated_at ?? now())->toAtomString(),
                ];
            }
        }

        foreach (Product::where('status', 'active')->orderBy('id')->get() as $prod) {
            if (! $noindexKeys->has(Product::class.':'.$prod->id)) {
                $urls[] = [
                    'loc' => route('products.show', $prod->slug),
                    'priority' => '0.8',
                    'changefreq' => 'daily',
                    'lastmod' => ($prod->updated_at ?? now())->toAtomString(),
                ];
            }
        }

        foreach (CmsPage::where('status', 'published')->orderBy('id')->get() as $page) {
            if (! $noindexKeys->has(CmsPage::class.':'.$page->id)) {
                $urls[] = [
                    'loc' => route('cms.pages.show', $page->slug),
                    'priority' => '0.6',
                    'changefreq' => 'monthly',
                    'lastmod' => ($page->updated_at ?? now())->toAtomString(),
                ];
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $loc = htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$loc}</loc>\n";
            $xml .= "    <lastmod>{$u['lastmod']}</lastmod>\n";
            $xml .= "    <changefreq>{$u['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$u['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= "</urlset>\n";

        return $xml;
    }

    /**
     * Generates robots.txt disallowing private, transactional, and staff routes.
     */
    public function generateRobotsTxt(): string
    {
        $sitemapUrl = route('seo.sitemap');

        return implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /manage',
            'Disallow: /account',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /orders',
            'Disallow: /private-files',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /demo-login',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            'Disallow: /two-factor-challenge',
            '',
            "Sitemap: {$sitemapUrl}",
            '',
        ]);
    }
}
