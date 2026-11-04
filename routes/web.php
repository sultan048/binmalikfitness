<?php

use App\Http\Controllers\Account\CustomerAccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Manage\BackOfficeController;
use App\Http\Controllers\Manage\CoachingManagementController;
use App\Http\Controllers\Manage\CommerceManagementController;
use App\Http\Controllers\Manage\GymManagementController;
use App\Http\Controllers\Manage\OperationsManagementController;
use App\Http\Controllers\Media\PrivateFileController;
use App\Http\Controllers\Public\CoachingAndProgramsController;
use App\Http\Controllers\Public\CommerceController;
use App\Http\Controllers\Public\GymController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\SeoAndContentController;
use Illuminate\Support\Facades\Route;

// Public Storefront & Design System Routes
Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/design-system', 'designSystem')->name('design.system');
});

// Stage 7 Technical SEO, Sitemap, Robots.txt & Published Legal/Editorial CMS Pages
Route::controller(SeoAndContentController::class)->group(function () {
    Route::get('/sitemap.xml', 'sitemap')->name('seo.sitemap');
    Route::get('/robots.txt', 'robots')->name('seo.robots');
    Route::get('/pages/{slug}', 'showCmsPage')->name('cms.pages.show');
});

// Stage 4 Online Coaching, Diet Plans, Workout Programs & Entitled Video Library Routes
Route::controller(CoachingAndProgramsController::class)->group(function () {
    Route::get('/coaching', 'coachingIndex')->name('coaching.index');
    Route::get('/coaching/{slug}', 'coachingShow')->name('coaching.show');
    Route::get('/diet-plans/{slug}', 'dietPlanShow')->name('diet-plans.show');
    Route::get('/videos/{slug}/watch', 'watchVideo')->name('videos.watch');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::post('/coaching/{slug}/enrol', 'enrolCoaching')->name('coaching.enrol');
        Route::post('/diet-plans/{slug}/purchase', 'purchaseDietPlan')->name('diet-plans.purchase');
        Route::post('/workout-programs/{slug}/purchase', 'purchaseWorkoutProgram')->name('workout-programs.purchase');

        Route::post('/account/coaching/intake', 'submitIntake')->name('account.coaching.intake.store');
        Route::post('/account/coaching/check-ins', 'submitCheckIn')->name('account.coaching.check-ins.store');
        Route::post('/account/coaching/workouts/{assignment}/log', 'logWorkoutCompletion')->name('account.coaching.workouts.log');
        Route::post('/account/coaching/consultations', 'bookConsultation')->name('account.coaching.consultations.store');
        Route::post('/account/coaching/messages', 'sendMessage')->name('account.coaching.messages.store');

        Route::post('/account/coaching/subscriptions/{subscription}/pause', 'pauseSubscription')->name('account.coaching.subscriptions.pause');
        Route::post('/account/coaching/subscriptions/{subscription}/resume', 'resumeSubscription')->name('account.coaching.subscriptions.resume');
        Route::post('/account/coaching/subscriptions/{subscription}/cancel', 'cancelSubscription')->name('account.coaching.subscriptions.cancel');
        Route::post('/account/coaching/subscriptions/{subscription}/renew', 'renewSubscription')->name('account.coaching.subscriptions.renew');

        Route::put('/account/diet-requests/{planRequest}/preferences', 'updateDietPreferences')->name('account.diet-requests.preferences.update');
    });
});

// Stage 5 Physical Gym Memberships, Class Schedule, Timings, Holidays & Trial Enquiries Routes
Route::controller(GymController::class)->group(function () {
    Route::get('/gym', 'index')->name('gym.index');
    Route::post('/gym/enquiries', 'submitEnquiry')->middleware('throttle:6,1')->name('gym.enquiries.store');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::post('/gym/memberships/{slug}/purchase', 'purchaseMembership')->name('gym.memberships.purchase');
        Route::post('/gym/classes/{gymClass}/book', 'bookClass')->name('gym.classes.book');
        Route::delete('/gym/class-bookings/{booking}', 'cancelClassBooking')->name('gym.class-bookings.cancel');
    });
});

// Stage 3 Supplement Ecommerce Storefront, Cart, Checkout & Order Lifecycle Routes
Route::controller(CommerceController::class)->group(function () {
    Route::get('/shop', 'shop')->name('shop.index');
    Route::get('/products/{slug}', 'productShow')->name('products.show');

    Route::get('/cart', 'cartCheckout')->name('cart.index');
    Route::post('/cart/items', 'addToCart')->middleware('throttle:30,1')->name('cart.items.store');
    Route::put('/cart/items/{cartItem}', 'updateCartItem')->middleware('throttle:30,1')->name('cart.items.update');
    Route::delete('/cart/items/{cartItem}', 'removeCartItem')->middleware('throttle:30,1')->name('cart.items.destroy');
    Route::post('/cart/coupon', 'applyCoupon')->middleware('throttle:10,1')->name('cart.coupon.apply');
    Route::delete('/cart/coupon', 'removeCoupon')->name('cart.coupon.remove');

    Route::post('/checkout', 'placeOrder')->name('checkout.place');

    Route::get('/orders/track', 'trackOrder')->middleware('throttle:15,1')->name('orders.track');
    Route::get('/orders/{orderNumber}/confirmation', 'orderConfirmation')->name('orders.confirmation');
    Route::get('/orders/{orderNumber}/invoice', 'orderInvoice')->name('orders.invoice');
    Route::post('/orders/{orderNumber}/payment-proof', 'submitPaymentProof')->name('orders.payment-proof.store');
    Route::post('/orders/{orderNumber}/cancel', 'cancelOrder')->name('orders.cancel');
    Route::post('/orders/{orderNumber}/return', 'requestReturn')->name('orders.return.store');

    Route::post('/wishlist/toggle', 'toggleWishlist')->middleware('throttle:30,1')->name('wishlist.toggle');
    Route::post('/stock-alerts', 'subscribeStockAlert')->middleware('throttle:6,1')->name('stock-alerts.store');

    Route::post('/products/{product}/reviews', 'submitReview')
        ->middleware(['auth', 'verified', 'throttle:10,1'])
        ->name('products.reviews.store');
});

// Authentication & Local Demo Switcher Routes
Route::controller(AuthenticatedSessionController::class)->group(function () {
    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login', 'login')->name('login.attempt');
    Route::post('/demo-login', 'demoSwitch')->name('demo.switch');
    Route::post('/logout', 'logout')->middleware('auth')->name('logout');
});

Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:10,1')->name('register.store');

// Password Recovery
Route::controller(PasswordResetController::class)->group(function () {
    Route::get('/forgot-password', 'showRequestForm')->name('password.request');
    Route::post('/forgot-password', 'sendResetLink')->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', 'showResetForm')->name('password.reset');
    Route::post('/reset-password', 'resetPassword')->middleware('throttle:6,1')->name('password.update');
});

// Email Verification & Two-Factor Authentication
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('/two-factor-challenge', [TwoFactorController::class, 'showChallenge'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorController::class, 'verifyChallenge'])->middleware('throttle:5,1')->name('two-factor.verify');
    Route::post('/two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/two-factor/confirm', [TwoFactorController::class, 'confirm'])->middleware('throttle:5,1')->name('two-factor.confirm');
    Route::post('/two-factor/disable', [TwoFactorController::class, 'disable'])->middleware('throttle:5,1')->name('two-factor.disable');
    Route::post('/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->middleware('throttle:5,1')->name('two-factor.recovery-codes');
    Route::get('/two-factor/setup', [TwoFactorController::class, 'showSetup'])->name('two-factor.setup');

    // Authorized Private File Streaming (never served from /public)
    Route::get('/private-files/{privateFile}', [PrivateFileController::class, 'download'])->name('private-files.download');
});

// Customer Portal (/account)
Route::middleware(['auth', 'verified'])->prefix('account')->name('account.')->controller(CustomerAccountController::class)->group(function () {
    Route::get('/', 'dashboard')->name('dashboard');
    Route::get('/orders/export', 'exportOrders')->middleware('throttle:10,1')->name('orders.export');
    Route::put('/profile', 'updateProfile')->name('profile.update');
    Route::put('/password', 'updatePassword')->name('password.update');
    Route::post('/addresses', 'storeAddress')->name('addresses.store');
    Route::delete('/addresses/{address}', 'destroyAddress')->name('addresses.destroy');
    Route::post('/private-files', 'uploadPrivateFile')->name('private-files.store');

    // Stage 6 Customer Support Tickets, Role Notifications & Privacy/Data Governance
    Route::post('/support-tickets', 'storeSupportTicket')->name('support-tickets.store');
    Route::post('/support-tickets/{ticket}/reply', 'replySupportTicket')->name('support-tickets.reply');
    Route::post('/notifications/{notification}/read', 'markNotificationRead')->name('notifications.read');
    Route::post('/privacy-requests', 'storePrivacyRequest')->name('privacy-requests.store');
});

// Staff Back-Office (/manage) — Role & Permission Protected in Backend
Route::middleware(['auth', 'verified'])->prefix('manage')->name('manage.')->group(function () {
    Route::controller(BackOfficeController::class)->group(function () {
        // Admin-only routes
        Route::get('/admin', 'adminDashboard')->middleware('role:admin')->name('admin');
        Route::put('/users/{user}/roles', 'updateUserRoles')->middleware('permission:roles.manage')->name('users.roles.update');
        Route::put('/settings', 'updateSettings')->middleware('permission:settings.manage')->name('settings.update');

        // Manager / Coach / Admin operational routes
        Route::get('/manager', 'managerDashboard')->middleware('role_or_permission:manager|coach|admin')->name('manager');
        Route::put('/users/{user}/status', 'toggleUserStatus')->middleware('permission:users.manage')->name('users.status.update');
        Route::post('/coach-assignments', 'assignCoach')->middleware('permission:coaching.subscriptions.manage')->name('coach-assignments.store');
        Route::post('/coach-assignments/{assignment}/approve', 'approveAssignment')->middleware('role:admin')->name('coach-assignments.approve');
        Route::post('/coach-assignments/{assignment}/reject', 'rejectAssignment')->middleware('role:admin')->name('coach-assignments.reject');
        Route::get('/clients/{client}/confidential', 'viewClientConfidential')->name('clients.confidential');
        Route::get('/exports/{dataset}', 'exportCsv')->middleware('throttle:20,1')->name('exports.csv');
    });

    // Stage 3 Catalog, Inventory, Order, Payment, Refund & Review Operations
    Route::controller(CommerceManagementController::class)->group(function () {
        Route::post('/products', 'storeProduct')->middleware('permission:pricing.manage')->name('products.store');
        Route::post('/products/{product}/documents', 'storeProductDocument')->middleware('permission:catalog.manage')->name('products.documents.store');

        Route::post('/variants/{variant}/batches', 'receiveBatch')->middleware('permission:inventory.receive')->name('variants.batches.store');
        Route::post('/variants/{variant}/stock-adjust', 'adjustStock')->middleware('permission:inventory.adjust')->name('variants.stock.adjust');

        Route::post('/coupons', 'storeCoupon')->middleware('permission:coupons.manage')->name('coupons.store');

        Route::post('/payments/{payment}/verify', 'verifyPayment')->middleware('permission:payments.verify')->name('payments.verify');
        Route::post('/payments/{payment}/reject', 'rejectPayment')->middleware('permission:payments.verify')->name('payments.reject');

        Route::put('/orders/{order}/status', 'updateOrderStatus')->middleware('permission:orders.fulfil')->name('orders.status.update');
        Route::post('/orders/{order}/refunds', 'issueRefund')->middleware('permission:refunds.approve')->name('orders.refunds.store');

        Route::put('/returns/{returnRequest}', 'resolveReturn')->middleware('permission:orders.fulfil')->name('returns.resolve');
        Route::put('/reviews/{review}', 'moderateReview')->middleware('permission:reviews.moderate')->name('reviews.moderate');
    });

    // Stage 4 Coaching, Intake Review, Personalised Diet Delivery & Client Operations
    Route::controller(CoachingManagementController::class)->group(function () {
        Route::post('/coaching-packages', 'storeCoachingPackage')->middleware('permission:coaching.packages.manage')->name('coaching-packages.store');
        Route::post('/diet-plans', 'storeDietPlan')->middleware('permission:diet.plans.manage')->name('diet-plans.store');

        Route::post('/intakes/{intake}/review', 'reviewIntake')->middleware('permission:health.view')->name('intakes.review');
        Route::post('/diet-requests/{planRequest}/deliver', 'deliverPersonalisedPlan')->middleware('permission:diet.requests.handle')->name('diet-requests.deliver');

        Route::post('/check-ins/{checkIn}/review', 'reviewCheckIn')->middleware('permission:coaching.deliver')->name('check-ins.review');
        Route::post('/workouts/assign', 'assignWorkout')->middleware('permission:coaching.deliver')->name('workouts.assign');
        Route::put('/consultations/{consultation}', 'updateConsultation')->middleware('permission:coaching.deliver')->name('consultations.update');
        Route::post('/clients/{client}/messages', 'sendCoachMessage')->middleware('permission:coaching.deliver')->name('clients.messages.store');
    });

    // Stage 5 Physical Gym Reception, Check-Ins, Counter Registrations, Classes, Timings & Enquiries
    Route::controller(GymManagementController::class)->group(function () {
        Route::post('/gym/counter-register', 'registerAtCounter')->middleware('permission:gym.memberships.manage')->name('gym.counter-register');
        Route::put('/gym/memberships/{membership}', 'updateMembership')->middleware('permission:gym.memberships.manage')->name('gym.memberships.update');
        Route::post('/gym/check-ins', 'recordCheckIn')->middleware('permission:gym.checkins.record')->name('gym.check-ins.store');

        Route::post('/gym/classes', 'saveClass')->middleware('permission:gym.classes.manage')->name('gym.classes.store');
        Route::put('/gym/classes/{gymClass}', 'saveClass')->middleware('permission:gym.classes.manage')->name('gym.classes.update');

        Route::post('/gym/plans', 'savePlan')->middleware('permission:gym.plans.manage')->name('gym.plans.store');
        Route::put('/gym/plans/{plan}', 'savePlan')->middleware('permission:gym.plans.manage')->name('gym.plans.update');

        Route::put('/gym/opening-hours/{hour}', 'updateOpeningHour')->middleware('permission:settings.manage')->name('gym.opening-hours.update');
        Route::post('/gym/holidays', 'saveHoliday')->middleware('permission:settings.manage')->name('gym.holidays.store');

        Route::put('/gym/enquiries/{enquiry}', 'updateEnquiry')->middleware('permission:enquiries.manage')->name('gym.enquiries.update');
    });

    // Stage 6 Complete Dashboards, Analytics Export, Support Desk, CMS, Media, SEO, Testimonials & Privacy Governance
    Route::controller(OperationsManagementController::class)->group(function () {
        Route::post('/support-tickets/{ticket}/reply', 'replyTicket')->middleware('permission:support.handle')->name('support-tickets.reply');
        Route::put('/support-tickets/{ticket}', 'updateTicket')->middleware('permission:support.handle')->name('support-tickets.update');

        Route::post('/cms-pages', 'saveCmsPage')->middleware('permission:content.manage')->name('cms-pages.store');
        Route::put('/cms-pages/{cmsPage}', 'saveCmsPage')->middleware('permission:content.manage')->name('cms-pages.update');

        Route::post('/media-assets', 'storeMediaAsset')->middleware('permission:media.manage')->name('media-assets.store');

        Route::post('/testimonials', 'saveTestimonial')->middleware('permission:testimonials.manage')->name('testimonials.store');
        Route::put('/testimonials/{testimonial}', 'saveTestimonial')->middleware('permission:testimonials.manage')->name('testimonials.update');

        Route::post('/seo-metadata', 'saveSeoMetadata')->middleware('permission:seo.manage')->name('seo-metadata.store');
        Route::post('/notification-templates', 'saveNotificationTemplate')->middleware('permission:notifications.manage')->name('notification-templates.store');

        Route::put('/privacy-requests/{privacyRequest}', 'resolvePrivacyRequest')->middleware('role:admin')->name('privacy-requests.resolve');
        Route::post('/privacy-requests/{privacyRequest}/anonymise', 'anonymiseAccount')->middleware('role:admin')->name('privacy-requests.anonymise');
        Route::post('/security/prune-tokens', 'pruneSecurityTokens')->middleware('permission:system.health')->name('security.prune-tokens');

        Route::get('/reports/orders-export', 'exportVerifiedOrdersCsv')->middleware('permission:reports.financial')->name('reports.orders-export');
    });
});
