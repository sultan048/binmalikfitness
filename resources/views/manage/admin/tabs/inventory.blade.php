{{-- Tab panel "inventory" of manage/admin-dashboard (included from the parent view, shares its variables) --}}
<div id="inventory-batches" class="grid grid-cols-1 gap-6 lg:grid-cols-12" data-tab-panel="inventory" x-show="tab === 'inventory'" x-cloak>
    <section class="lg:col-span-7 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl uppercase text-graphite">Inventory Batches, Expiry & Stock Control</h2>
            <x-ui.badge tone="gold">Atomic FEFO</x-ui.badge>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-graphite/15 text-gold-dark uppercase">
                        <th class="py-2 pr-2">SKU & Variant</th>
                        <th class="py-2 px-2">On Hand / Reserved</th>
                        <th class="py-2 px-2">Receive Batch</th>
                        <th class="py-2 pl-2">Adjust Stock</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-graphite/10">
                    @foreach ($variants as $v)
                        <tr>
                            <td class="py-2.5 pr-2">
                                <strong class="font-mono text-graphite block">{{ $v->sku }}</strong>
                                <span class="text-[11px] text-graphite/65">{{ $v->product?->name }} ({{ $v->label() }})</span>
                            </td>
                            <td class="py-2.5 px-2">
                                <span class="font-bold text-success">{{ $v->availableStock() }} avail</span>
                                <span class="block text-[11px] text-graphite/60">On Hand: {{ $v->stock_on_hand }} · Res: {{ $v->stock_reserved }}</span>
                            </td>
                            <td class="py-2.5 px-2">
                                <form method="POST" action="{{ route('manage.variants.batches.store', $v) }}" class="flex items-center gap-1">
                                    @csrf
                                    <input type="text" name="batch_number" placeholder="LOT-2026B" required class="field-input py-1 text-[11px] w-24" />
                                    <input type="number" name="quantity_received" value="10" min="1" required class="field-input py-1 text-[11px] w-14" />
                                    <input type="date" name="expires_at" value="{{ now()->addMonths(18)->toDateString() }}" class="field-input py-1 text-[11px] w-28" />
                                    <button type="submit" class="rounded-xs bg-ink px-2 py-1 text-[10px] font-bold uppercase text-gold cursor-pointer">+Batch</button>
                                </form>
                            </td>
                            <td class="py-2.5 pl-2">
                                <form method="POST" action="{{ route('manage.variants.stock.adjust', $v) }}" class="flex items-center gap-1">
                                    @csrf
                                    <input type="number" name="delta" value="5" required class="field-input py-1 text-[11px] w-14" />
                                    <input type="hidden" name="reason" value="Cycle count adjustment" />
                                    <button type="submit" class="rounded-xs border border-graphite px-2 py-1 text-[10px] font-bold uppercase text-graphite cursor-pointer">Adj</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section id="catalog-admin" class="lg:col-span-5 rounded-sm border border-graphite/12 bg-white p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-2xl uppercase text-graphite">Add Supplement / Gear Product</h2>
            <x-ui.badge tone="light">catalog.manage</x-ui.badge>
        </div>
        <form method="POST" action="{{ route('manage.products.store') }}" class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 text-xs">
            @csrf
            <div class="sm:col-span-2">
                <label class="field-label text-xs">Product Name</label>
                <input type="text" name="name" required placeholder="[DEMO] Essential Amino Acids (EAA) — 30 Servings" class="field-input text-xs" />
            </div>
            <div>
                <label class="field-label text-xs">Category</label>
                <select name="category_id" class="field-input text-xs" required>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label text-xs">Brand</label>
                <select name="brand_id" class="field-input text-xs">
                    @foreach ($brands as $br)
                        <option value="{{ $br->id }}">{{ $br->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label text-xs">Product SKU</label>
                <input type="text" name="sku" required placeholder="DEMO-EAA-30" class="field-input text-xs uppercase" />
            </div>
            <div>
                <label class="field-label text-xs">Size & Flavor</label>
                <input type="text" name="size" value="300 g (30 Servings)" required class="field-input text-xs" />
            </div>
            <div>
                <label class="field-label text-xs">Retail Price (PKR)</label>
                <input type="number" name="price_pkr" value="7200" min="1" required class="field-input text-xs" />
            </div>
            <div>
                <label class="field-label text-xs">Initial Stock</label>
                <input type="number" name="stock_on_hand" value="15" min="0" required class="field-input text-xs" />
            </div>
            <div class="sm:col-span-2">
                <label class="field-label text-xs">Regulatory Documentation Status</label>
                <select name="regulatory_status" class="field-input text-xs">
                    <option value="pending_verification">Pending Importer/DRAP Verification</option>
                    <option value="verified">Verified Importer & Batch COA</option>
                    <option value="exempt_accessory">Exempt Gym Accessory</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <x-ui.button type="submit" variant="graphite" size="sm" class="w-full">Create Catalog Product & Default Variant</x-ui.button>
            </div>
        </form>
    </section>
</div>
