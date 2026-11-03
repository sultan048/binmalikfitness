<footer class="border-t border-white/10 bg-ink text-silver">
    <div class="container-site py-14">
        <div class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2 space-y-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xs border border-gold bg-charcoal font-display text-xl text-gold">
                        BM
                    </span>
                    <span class="font-display text-2xl tracking-wider text-ivory">BIN MALIK FITNESS</span>
                </div>
                <p class="max-w-md text-sm leading-relaxed text-silver">
                    Disciplined online strength & nutrition coaching, batch-tracked sports supplements in PKR, structured South Asian diet plans, and physical strength training in Pakistan.
                </p>
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <x-ui.demo-flag label="Stage 1 Architecture & Design Prototype" />
                    <span class="text-xs text-silver/80">Currency: PKR · Primary Market: Pakistan</span>
                </div>
            </div>

            <div>
                <h3 class="eyebrow text-gold">Coaching & Plans</h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('coaching.index') }}" class="hover:text-ivory">1-on-1 Online Coaching</a></li>
                    <li><a href="{{ route('coaching.show', '12-week-complete-recomposition') }}" class="hover:text-ivory">12-Week Recomposition</a></li>
                    <li><a href="{{ route('diet-plans.show', 'pakistani-fat-loss-blueprint-demo') }}" class="hover:text-ivory">Pakistani Fat-Loss Diet Plan</a></li>
                    <li><a href="{{ route('home') }}#free-previews" class="hover:text-ivory">Free Training Previews</a></li>
                </ul>
            </div>

            <div>
                <h3 class="eyebrow text-gold">Supplement Shop</h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('shop.index', ['category' => 'whey-protein']) }}" class="hover:text-ivory">Whey Protein</a></li>
                    <li><a href="{{ route('shop.index', ['category' => 'creatine']) }}" class="hover:text-ivory">Creatine Monohydrate</a></li>
                    <li><a href="{{ route('shop.index', ['category' => 'pre-workout']) }}" class="hover:text-ivory">Pre-Workout</a></li>
                    <li><a href="{{ route('shop.index', ['category' => 'gym-accessories']) }}" class="hover:text-ivory">Gym Accessories</a></li>
                    <li><a href="{{ route('cart.index') }}" class="hover:text-ivory">Cart & COD / Bank Checkout</a></li>
                </ul>
            </div>

            <div>
                <h3 class="eyebrow text-gold">Account & Governance</h3>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('login') }}" class="hover:text-ivory">Member Login / Register</a></li>
                    <li><a href="{{ route('account.dashboard') }}" class="hover:text-ivory">Customer Dashboard</a></li>
                    <li><a href="{{ route('manage.manager') }}" class="hover:text-ivory">Manager Operations View</a></li>
                    <li><a href="{{ route('manage.admin') }}" class="hover:text-ivory">Admin Control View</a></li>
                    <li><a href="{{ route('design.system') }}" class="hover:text-ivory">Brand Design System</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-12 border-t border-white/10 pt-6 text-xs text-silver/80 flex flex-col gap-4">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-silver">
                <a href="{{ route('cms.pages.show', 'terms-and-conditions') }}" class="hover:text-gold transition">Terms &amp; Conditions</a>
                <a href="{{ route('cms.pages.show', 'privacy-and-health-data-policy') }}" class="hover:text-gold transition">Privacy &amp; Health Data Policy</a>
                <a href="{{ route('cms.pages.show', 'refund-return-and-shipping-policy') }}" class="hover:text-gold transition">Refund, Return &amp; Shipping Policy</a>
                <a href="{{ route('cms.pages.show', 'pakistani-whole-food-protein-guide') }}" class="hover:text-gold transition">Pakistani Whole-Food Protein Guide</a>
                <a href="{{ route('seo.sitemap') }}" class="hover:text-gold transition font-mono">XML Sitemap</a>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p>© {{ date('Y') }} BIN MALIK FITNESS (binmalikfitness.com). All rights reserved.</p>
                <p>Health &amp; nutrition programs are educational for healthy adults and do not replace physician medical advice.</p>
            </div>
        </div>
    </div>
</footer>
