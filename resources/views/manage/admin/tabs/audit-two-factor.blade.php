{{-- Tab panel "audit" of manage/admin-dashboard (included from the parent view, shares its variables) --}}
<div data-tab-panel="audit" x-show="tab === 'audit'" x-cloak>
    <x-auth.two-factor-panel />
</div>
