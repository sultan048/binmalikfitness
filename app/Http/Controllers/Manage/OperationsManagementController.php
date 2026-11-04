<?php

namespace App\Http\Controllers\Manage;

use App\Domain\Engagement\SupportAndOperationsService;
use App\Domain\Platform\AnalyticsAndReportingService;
use App\Domain\Platform\AuditLogger;
use App\Domain\Platform\DataRetentionAndSecurityService;
use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\PrivacyRequest;
use App\Models\SupportTicket;
use App\Models\Testimonial;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class OperationsManagementController extends Controller
{
    public function __construct(
        private readonly SupportAndOperationsService $operations,
        private readonly AnalyticsAndReportingService $analytics,
        private readonly AuditLogger $audit,
        private readonly DataRetentionAndSecurityService $retention,
    ) {}

    public function replyTicket(Request $request, SupportTicket $ticket): RedirectResponse
    {
        if (! $request->user()->can('support.handle')) {
            abort(403, 'You are not authorized to reply to support tickets.');
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'is_internal_note' => ['nullable', 'boolean'],
        ]);

        $this->operations->replyToTicket(
            ticket: $ticket,
            actor: $request->user(),
            messageBody: $validated['message'],
            isInternalNote: $request->boolean('is_internal_note'),
        );

        return back()->with('status', "Reply posted to Support Ticket {$ticket->ticket_number}.");
    }

    public function updateTicket(Request $request, SupportTicket $ticket): RedirectResponse
    {
        if (! $request->user()->can('support.handle')) {
            abort(403, 'You are not authorized to update support tickets.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:open,in_progress,awaiting_customer,resolved,closed'],
            'priority' => ['required', 'string', 'in:low,normal,high,urgent'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $this->operations->updateTicketGovernance($ticket, $request->user(), $validated);

        return back()->with('status', "Support Ticket {$ticket->ticket_number} updated to {$validated['status']}.");
    }

    public function saveCmsPage(Request $request, ?CmsPage $cmsPage = null): RedirectResponse
    {
        if (! $request->user()->can('content.manage')) {
            abort(403, 'Only Administrators may manage CMS pages.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160'],
            'section' => ['required', 'string', 'in:policy,editorial,faq,landing'],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string'],
            'status' => ['required', 'string', 'in:draft,published,archived'],
        ]);

        $saved = $this->operations->saveCmsPage($request->user(), $validated, $cmsPage);

        return back()->with('status', "Saved CMS page '{$saved->title}' ({$saved->status}).");
    }

    public function storeMediaAsset(Request $request): RedirectResponse
    {
        if (! $request->user()->can('media.manage')) {
            abort(403, 'Only Administrators may manage the media library.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'collection' => ['required', 'string', 'in:hero,coaching,shop,gym,general'],
            'path' => ['required', 'string', 'max:255'],
            'alt_text' => ['required', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:80'],
        ]);

        $asset = $this->operations->storeMediaAsset($request->user(), $validated);

        return back()->with('status', "Registered media asset '{$asset->title}' in {$asset->collection} collection.");
    }

    public function saveTestimonial(Request $request, ?Testimonial $testimonial = null): RedirectResponse
    {
        if (! $request->user()->can('testimonials.manage')) {
            abort(403, 'Only Administrators may manage testimonials.');
        }

        $validated = $request->validate([
            'author_label' => ['required', 'string', 'max:120'],
            'program_context' => ['required', 'string', 'max:120'],
            'quote' => ['required', 'string', 'max:1000'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'consent_record_id' => ['nullable', 'exists:consent_records,id'],
            'status' => ['required', 'string', 'in:draft,published'],
        ]);

        $saved = $this->operations->saveTestimonial($request->user(), $validated, $testimonial);

        return back()->with('status', "Saved testimonial for {$saved->author_label} ({$saved->status}).");
    }

    public function saveSeoMetadata(Request $request): RedirectResponse
    {
        if (! $request->user()->can('seo.manage')) {
            abort(403, 'Only Administrators may manage SEO settings.');
        }

        $validated = $request->validate([
            'seoable_type' => ['required', 'string', 'max:120'],
            'seoable_id' => ['required', 'integer', 'min:1'],
            'meta_title' => ['required', 'string', 'max:160'],
            'meta_description' => ['required', 'string', 'max:320'],
            'canonical_url' => ['nullable', 'string', 'max:255'],
            'robots' => ['required', 'string', Rule::in(['index,follow', 'noindex,nofollow'])],
        ]);

        $seo = $this->operations->saveSeoMetadata($request->user(), $validated);

        return back()->with('status', "Updated SEO metadata for {$seo->meta_title}.");
    }

    public function saveNotificationTemplate(Request $request): RedirectResponse
    {
        if (! $request->user()->can('notifications.manage')) {
            abort(403, 'Only Administrators may manage notification templates.');
        }

        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:140'],
            'channel' => ['required', 'string', 'in:email,database,whatsapp_consent'],
            'subject' => ['nullable', 'string', 'max:190'],
            'body_template' => ['required', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $template = $this->operations->saveNotificationTemplate($request->user(), $validated);

        return back()->with('status', "Saved notification template '{$template->name}' ({$template->key}).");
    }

    public function resolvePrivacyRequest(Request $request, PrivacyRequest $privacyRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:in_review,completed,rejected'],
            'resolution_notes' => ['required', 'string', 'max:500'],
        ]);

        $this->operations->resolvePrivacyRequest($privacyRequest, $request->user(), $validated);

        return back()->with('status', "Resolved Privacy Request #{$privacyRequest->id} ({$validated['status']}).");
    }

    public function exportVerifiedOrdersCsv(Request $request): Response
    {
        if (! $request->user()->can('reports.financial')) {
            abort(403, 'Only Administrators with financial reporting permissions may export reconciled order ledgers.');
        }

        $from = $request->filled('date_from')
            ? Carbon::parse((string) $request->query('date_from'))->startOfDay()
            : now()->subDays(90)->startOfDay();

        $to = $request->filled('date_to')
            ? Carbon::parse((string) $request->query('date_to'))->endOfDay()
            : now()->endOfDay();

        $csv = $this->analytics->exportVerifiedOrdersCsv($from, $to);

        $this->audit->record(
            action: 'reports.verified_orders.csv_exported',
            subject: null,
            oldValues: null,
            newValues: ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            actor: $request->user(),
        );

        $filename = 'binmalikfitness-reconciled-orders-'.$from->format('Ymd').'-to-'.$to->format('Ymd').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function pruneSecurityTokens(Request $request): RedirectResponse
    {
        if (! $request->user()->can('system.health')) {
            abort(403, 'Only Administrators may prune expired security tokens and stale guest carts.');
        }

        $summary = $this->retention->pruneExpiredSecurityTokens($request->user());

        return back()->with(
            'status',
            "Security retention sweep complete: pruned {$summary['expired_password_tokens']} expired reset tokens, {$summary['expired_idempotency_keys']} idempotency keys, and {$summary['stale_guest_carts']} stale guest carts."
        );
    }

    public function anonymiseAccount(Request $request, PrivacyRequest $privacyRequest): RedirectResponse
    {
        $anonymisedUser = $this->retention->anonymiseCustomerAccount($privacyRequest, $request->user());

        return back()->with(
            'status',
            "Account PII and confidential health records anonymised ({$anonymisedUser->name}) while preserving immutable financial order ledgers."
        );
    }
}
