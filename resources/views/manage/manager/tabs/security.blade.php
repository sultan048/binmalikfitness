{{-- Tab panel "security" of manage/manager-dashboard (included from the parent view, shares its variables) --}}
<div data-tab-panel="security" x-show="tab === 'security'" x-cloak>
    <x-auth.two-factor-panel />
</div>
