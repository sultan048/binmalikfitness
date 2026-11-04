<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CoachingPackage;
use App\Models\DietPlan;
use App\Models\Faq;
use App\Models\MembershipPlan;
use App\Models\OpeningHour;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StaffProfile;
use App\Models\Testimonial;
use App\Models\Transformation;
use App\Models\Video;
use App\Support\Demo;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function home(): View
    {
        return view('public.home', [
            'coachingPackages' => CoachingPackage::query()
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->get(),
            'dietPlans' => DietPlan::query()
                ->where('status', 'active')
                ->where('is_featured', true)
                ->take(3)
                ->get(),
            'bestsellerProducts' => Product::query()
                ->with(['category', 'brand', 'defaultVariant', 'variants', 'primaryImage'])
                ->where('status', 'active')
                ->where('is_bestseller', true)
                ->take(4)
                ->get(),
            'headCoach' => StaffProfile::query()
                ->with('user')
                ->where('is_public_coach', true)
                ->first(),
            'freeVideos' => Video::query()
                ->where('access', 'public')
                ->where('is_featured', true)
                ->take(3)
                ->get(),
            'transformations' => Transformation::query()
                ->where('status', 'published')
                ->whereNotNull('consent_record_id')
                ->get(),
            'testimonials' => Testimonial::query()
                ->where('status', 'published')
                ->whereNotNull('consent_record_id')
                ->get(),
            'faqs' => Faq::query()
                ->where('is_published', true)
                ->orderBy('sort_order')
                ->get(),
            'openingHours' => OpeningHour::query()
                ->orderBy('day_of_week')
                ->get(),
            'membershipPlans' => MembershipPlan::query()
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->get(),
            'gymCity' => Setting::getValue('gym.city', 'City Pending Owner Confirmation'),
            'gymAddress' => Setting::getValue('gym.address', 'Facility address pending owner confirmation'),
            'gymPhone' => Setting::getValue('gym.phone', '+92 300 0000000 (Demo)'),
        ]);
    }

    /**
     * Internal UI component reference; only reachable while demo mode is on.
     */
    public function designSystem(): View
    {
        abort_unless(Demo::enabled(), 404);

        return view('public.design-system');
    }
}
