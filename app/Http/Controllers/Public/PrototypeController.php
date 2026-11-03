<?php

namespace App\Http\Controllers\Public;

use App\Domain\Identity\RolePermissionMatrix;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CoachingPackage;
use App\Models\DietPlan;
use App\Models\Faq;
use App\Models\MembershipPlan;
use App\Models\OpeningHour;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\StaffProfile;
use App\Models\Testimonial;
use App\Models\Transformation;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrototypeController extends Controller
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

    public function shop(Request $request): View
    {
        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $brands = Brand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $productsQuery = Product::query()
            ->with(['category', 'brand', 'defaultVariant', 'variants', 'primaryImage'])
            ->where('status', 'active');

        if ($request->filled('category')) {
            $productsQuery->whereHas('category', fn ($q) => $q->where('slug', $request->string('category')));
        }

        if ($request->filled('brand')) {
            $productsQuery->whereHas('brand', fn ($q) => $q->where('slug', $request->string('brand')));
        }

        if ($request->filled('q')) {
            $search = '%'.$request->string('q').'%';
            $productsQuery->where(fn ($q) => $q->where('name', 'like', $search)->orWhere('sku', 'like', $search));
        }

        return view('public.shop.index', [
            'categories' => $categories,
            'brands' => $brands,
            'products' => $productsQuery->get(),
            'activeCategory' => $request->string('category')->toString(),
            'activeBrand' => $request->string('brand')->toString(),
            'searchQuery' => $request->string('q')->toString(),
        ]);
    }

    public function productShow(string $slug): View
    {
        $product = Product::query()
            ->with(['category', 'brand', 'variants', 'images', 'primaryImage'])
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedProducts = Product::query()
            ->with(['category', 'defaultVariant', 'variants', 'primaryImage'])
            ->where('status', 'active')
            ->whereKeyNot($product->id)
            ->take(4)
            ->get();

        return view('public.shop.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }

    public function cartCheckout(): View
    {
        $sampleProducts = Product::query()
            ->with(['defaultVariant', 'primaryImage'])
            ->where('status', 'active')
            ->take(2)
            ->get();

        return view('public.shop.cart-checkout', [
            'sampleProducts' => $sampleProducts,
        ]);
    }

    public function coachingIndex(): View
    {
        return view('public.coaching.index', [
            'packages' => CoachingPackage::query()->where('status', 'active')->orderBy('sort_order')->get(),
            'dietPlans' => DietPlan::query()->where('status', 'active')->get(),
            'headCoach' => StaffProfile::query()->with('user')->where('is_public_coach', true)->first(),
        ]);
    }

    public function coachingShow(string $slug): View
    {
        $package = CoachingPackage::query()->where('slug', $slug)->firstOrFail();
        $otherPackages = CoachingPackage::query()->whereKeyNot($package->id)->orderBy('sort_order')->get();

        return view('public.coaching.show', [
            'package' => $package,
            'otherPackages' => $otherPackages,
        ]);
    }

    public function dietPlanShow(string $slug): View
    {
        $plan = DietPlan::query()->where('slug', $slug)->firstOrFail();
        $otherPlans = DietPlan::query()->whereKeyNot($plan->id)->get();

        return view('public.programs.diet-show', [
            'plan' => $plan,
            'otherPlans' => $otherPlans,
        ]);
    }

    public function loginRegister(): View
    {
        return view('auth.login-register');
    }

    public function adminDashboard(): View
    {
        return view('manage.admin-dashboard', [
            'productsCount' => Product::count(),
            'categoriesCount' => ProductCategory::count(),
            'coachingPackagesCount' => CoachingPackage::count(),
            'dietPlansCount' => DietPlan::count(),
            'permissionsCount' => count(RolePermissionMatrix::permissions()),
            'roleMap' => RolePermissionMatrix::roleMap(),
        ]);
    }

    public function managerDashboard(): View
    {
        return view('manage.manager-dashboard', [
            'managerPermissions' => RolePermissionMatrix::roleMap()[RolePermissionMatrix::ROLE_MANAGER],
            'products' => Product::with('defaultVariant')->take(4)->get(),
        ]);
    }

    public function customerDashboard(): View
    {
        return view('account.dashboard', [
            'featuredPackage' => CoachingPackage::where('is_featured', true)->first(),
            'featuredDiet' => DietPlan::where('is_featured', true)->first(),
            'membershipPlan' => MembershipPlan::where('is_featured', true)->first(),
        ]);
    }

    public function designSystem(): View
    {
        return view('public.design-system');
    }
}
