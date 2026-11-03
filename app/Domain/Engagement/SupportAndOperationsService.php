<?php

namespace App\Domain\Engagement;

use App\Domain\Identity\RolePermissionMatrix;
use App\Domain\Platform\AuditLogger;
use App\Models\CmsPage;
use App\Models\ConsentRecord;
use App\Models\MediaAsset;
use App\Models\NotificationTemplate;
use App\Models\PrivacyRequest;
use App\Models\RoleNotification;
use App\Models\SeoMetadata;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SupportAndOperationsService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function createTicket(User $customer, array $data): SupportTicket
    {
        return DB::transaction(function () use ($customer, $data) {
            $ticketNumber = 'TKT-'.now()->format('ymd').'-'.strtoupper(Str::random(4));

            $ticket = SupportTicket::create([
                'ticket_number' => $ticketNumber,
                'user_id' => $customer->id,
                'order_id' => $data['order_id'] ?? null,
                'category' => $data['category'],
                'priority' => $data['priority'] ?? 'normal',
                'status' => SupportTicket::STATUS_OPEN,
                'subject' => trim($data['subject']),
                'last_reply_at' => now(),
            ]);

            SupportTicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'sender_id' => $customer->id,
                'message' => trim($data['message']),
                'is_internal_note' => false,
            ]);

            $staffUsers = User::role([RolePermissionMatrix::ROLE_ADMIN, RolePermissionMatrix::ROLE_MANAGER])->get();
            foreach ($staffUsers as $staff) {
                RoleNotification::create([
                    'user_id' => $staff->id,
                    'role_scope' => $staff->hasRole(RolePermissionMatrix::ROLE_ADMIN) ? 'admin' : 'manager',
                    'type' => 'support.ticket_created',
                    'title' => "New Support Ticket {$ticket->ticket_number}",
                    'body' => "{$customer->name} opened a {$ticket->category} ticket: {$ticket->subject}",
                    'action_url' => $staff->hasRole(RolePermissionMatrix::ROLE_ADMIN) ? '/manage/admin#support-tickets' : '/manage/manager#support-tickets',
                ]);
            }

            $this->audit->record(
                action: 'support.ticket.created',
                subject: $ticket,
                oldValues: null,
                newValues: [
                    'ticket_number' => $ticket->ticket_number,
                    'category' => $ticket->category,
                    'priority' => $ticket->priority,
                ],
                actor: $customer,
            );

            return $ticket->load('messages.sender');
        });
    }

    public function replyToTicket(
        SupportTicket $ticket,
        User $actor,
        string $messageBody,
        bool $isInternalNote = false
    ): SupportTicketMessage {
        $isStaff = $actor->can('support.handle');

        if (! $isStaff && $ticket->user_id !== $actor->id) {
            abort(403, 'You are not authorized to reply to this support ticket.');
        }

        // Coaches without full manager/admin role may only handle tickets assigned to them or their assigned clients
        if ($isStaff && $actor->hasRole(RolePermissionMatrix::ROLE_COACH) && ! $actor->hasAnyRole([RolePermissionMatrix::ROLE_ADMIN, RolePermissionMatrix::ROLE_MANAGER])) {
            if ($ticket->assigned_to !== $actor->id && ! $actor->isAssignedToClient($ticket->user_id)) {
                abort(403, 'Coaches may only handle support tickets assigned to them or their assigned coaching clients.');
            }
        }

        if (! $isStaff) {
            $isInternalNote = false;
        }

        return DB::transaction(function () use ($ticket, $actor, $messageBody, $isInternalNote, $isStaff) {
            $message = SupportTicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'sender_id' => $actor->id,
                'message' => trim($messageBody),
                'is_internal_note' => $isInternalNote,
            ]);

            if (! $isInternalNote) {
                $nextStatus = $isStaff
                    ? SupportTicket::STATUS_AWAITING_CUSTOMER
                    : SupportTicket::STATUS_IN_PROGRESS;

                $ticket->update([
                    'status' => $ticket->status === SupportTicket::STATUS_CLOSED ? $ticket->status : $nextStatus,
                    'last_reply_at' => now(),
                    'assigned_to' => $ticket->assigned_to ?? ($isStaff ? $actor->id : null),
                ]);

                if ($isStaff) {
                    RoleNotification::create([
                        'user_id' => $ticket->user_id,
                        'role_scope' => 'customer',
                        'type' => 'support.ticket_replied',
                        'title' => "Update on Ticket {$ticket->ticket_number}",
                        'body' => "{$actor->name} replied to your support request: {$ticket->subject}",
                        'action_url' => '/account#support-tickets',
                    ]);
                }
            }

            $this->audit->record(
                action: $isInternalNote ? 'support.ticket.internal_note_added' : 'support.ticket.replied',
                subject: $ticket,
                oldValues: null,
                newValues: [
                    'ticket_number' => $ticket->ticket_number,
                    'message_id' => $message->id,
                    'is_internal_note' => $isInternalNote,
                ],
                actor: $actor,
            );

            return $message;
        });
    }

    public function updateTicketGovernance(SupportTicket $ticket, User $staff, array $data): SupportTicket
    {
        if (! $staff->can('support.handle')) {
            abort(403, 'You are not authorized to manage support tickets.');
        }

        $oldValues = $ticket->only(['status', 'priority', 'assigned_to']);
        $newStatus = $data['status'] ?? $ticket->status;

        $ticket->update([
            'status' => $newStatus,
            'priority' => $data['priority'] ?? $ticket->priority,
            'assigned_to' => array_key_exists('assigned_to', $data) ? $data['assigned_to'] : $ticket->assigned_to,
            'resolved_at' => in_array($newStatus, [SupportTicket::STATUS_RESOLVED, SupportTicket::STATUS_CLOSED], true)
                ? ($ticket->resolved_at ?? now())
                : null,
        ]);

        $this->audit->record(
            action: 'support.ticket.updated',
            subject: $ticket,
            oldValues: $oldValues,
            newValues: $ticket->only(['status', 'priority', 'assigned_to']),
            actor: $staff,
        );

        return $ticket->fresh();
    }

    public function saveCmsPage(User $admin, array $data, ?CmsPage $page = null): CmsPage
    {
        if (! $admin->can('content.manage')) {
            abort(403, 'Only Administrators with content permissions may manage CMS pages.');
        }

        $slug = Str::slug($data['slug'] ?? $data['title']);
        $status = $data['status'] ?? 'published';

        $attributes = [
            'title' => trim($data['title']),
            'slug' => $slug,
            'section' => $data['section'] ?? 'policy',
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'],
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
            'updated_by' => $admin->id,
        ];

        if ($page) {
            $old = $page->only(['title', 'slug', 'status']);
            $page->update($attributes);
            $this->audit->record('cms.page.updated', $page, $old, $page->only(['title', 'slug', 'status']), $admin);

            return $page->fresh();
        }

        $created = CmsPage::updateOrCreate(['slug' => $slug], $attributes);
        $this->audit->record('cms.page.saved', $created, null, ['slug' => $created->slug, 'status' => $created->status], $admin);

        return $created;
    }

    public function storeMediaAsset(User $admin, array $data): MediaAsset
    {
        if (! $admin->can('media.manage')) {
            abort(403, 'Only Administrators with media permissions may manage the media library.');
        }

        $asset = MediaAsset::create([
            'title' => trim($data['title']),
            'collection' => $data['collection'] ?? 'general',
            'disk' => $data['disk'] ?? 'public',
            'path' => trim($data['path']),
            'mime_type' => $data['mime_type'] ?? 'image/png',
            'alt_text' => trim($data['alt_text']),
            'size_bytes' => (int) ($data['size_bytes'] ?? 0),
            'uploaded_by' => $admin->id,
        ]);

        $this->audit->record('media.asset.stored', $asset, null, ['title' => $asset->title, 'path' => $asset->path], $admin);

        return $asset;
    }

    public function saveTestimonial(User $admin, array $data, ?Testimonial $testimonial = null): Testimonial
    {
        if (! $admin->can('testimonials.manage')) {
            abort(403, 'Only Administrators may manage public testimonials.');
        }

        $status = $data['status'] ?? 'draft';
        $consentId = $data['consent_record_id'] ?? $testimonial?->consent_record_id;

        if ($status === 'published') {
            $consent = $consentId ? ConsentRecord::find($consentId) : null;
            if (! $consent || $consent->withdrawn_at !== null) {
                throw ValidationException::withMessages([
                    'consent_record_id' => ['Public testimonials require an active, non-withdrawn ConsentRecord before publishing.'],
                ]);
            }
        }

        $attributes = [
            'display_name' => trim($data['author_label']),
            'author_label' => trim($data['author_label']),
            'context_label' => trim($data['program_context']),
            'program_context' => trim($data['program_context']),
            'quote' => trim($data['quote']),
            'rating' => (int) ($data['rating'] ?? 5),
            'consent_record_id' => $consentId,
            'verification_note' => $consentId ? "Verified via ConsentRecord #{$consentId}" : 'Draft pending consent',
            'status' => $status,
            'is_demo' => (bool) ($data['is_demo'] ?? false),
        ];

        if ($testimonial) {
            $testimonial->update($attributes);
            $this->audit->record('engagement.testimonial.updated', $testimonial, null, ['status' => $status], $admin);

            return $testimonial->fresh();
        }

        $created = Testimonial::create($attributes);
        $this->audit->record('engagement.testimonial.created', $created, null, ['author_label' => $created->author_label, 'status' => $status], $admin);

        return $created;
    }

    public function saveSeoMetadata(User $admin, array $data): SeoMetadata
    {
        if (! $admin->can('seo.manage')) {
            abort(403, 'Only Administrators with SEO permissions may update SEO metadata.');
        }

        $seo = SeoMetadata::updateOrCreate(
            [
                'seoable_type' => $data['seoable_type'],
                'seoable_id' => (int) $data['seoable_id'],
            ],
            [
                'title' => trim($data['meta_title']),
                'meta_title' => trim($data['meta_title']),
                'description' => trim($data['meta_description']),
                'meta_description' => trim($data['meta_description']),
                'canonical_url' => $data['canonical_url'] ?? null,
                'og_image' => $data['og_image'] ?? null,
                'robots' => $data['robots'] ?? 'index,follow',
            ]
        );

        $this->audit->record('seo.metadata.updated', $seo, null, [
            'seoable_type' => $seo->seoable_type,
            'seoable_id' => $seo->seoable_id,
            'meta_title' => $seo->meta_title,
        ], $admin);

        return $seo;
    }

    public function saveNotificationTemplate(User $admin, array $data): NotificationTemplate
    {
        if (! $admin->can('notifications.manage')) {
            abort(403, 'Only Administrators may manage notification templates.');
        }

        $template = NotificationTemplate::updateOrCreate(
            ['key' => trim($data['key'])],
            [
                'name' => trim($data['name']),
                'channel' => $data['channel'] ?? 'database',
                'subject' => $data['subject'] ?? null,
                'body_template' => trim($data['body_template']),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]
        );

        $this->audit->record('notifications.template.saved', $template, null, ['key' => $template->key, 'channel' => $template->channel], $admin);

        return $template;
    }

    public function submitPrivacyRequest(User $customer, array $data): PrivacyRequest
    {
        return DB::transaction(function () use ($customer, $data) {
            $requestRecord = PrivacyRequest::create([
                'user_id' => $customer->id,
                'request_type' => $data['request_type'],
                'status' => PrivacyRequest::STATUS_PENDING,
                'customer_notes' => $data['customer_notes'] ?? null,
            ]);

            if ($data['request_type'] === PrivacyRequest::TYPE_CONSENT_WITHDRAWAL && ! empty($data['consent_record_id'])) {
                $consent = ConsentRecord::where('user_id', $customer->id)->find($data['consent_record_id']);
                if ($consent && $consent->withdrawn_at === null) {
                    $consent->update(['withdrawn_at' => now()]);
                    // Unpublish any public testimonial tied to this withdrawn consent
                    Testimonial::where('consent_record_id', $consent->id)->update(['status' => 'draft']);
                }
            }

            $this->audit->record('privacy.request.submitted', $requestRecord, null, [
                'request_type' => $requestRecord->request_type,
            ], $customer);

            return $requestRecord;
        });
    }

    public function resolvePrivacyRequest(PrivacyRequest $privacyRequest, User $admin, array $data): PrivacyRequest
    {
        if (! $admin->can('users.manage') || ! $admin->hasRole(RolePermissionMatrix::ROLE_ADMIN)) {
            abort(403, 'Only Administrators may resolve privacy & data governance requests.');
        }

        $privacyRequest->update([
            'status' => $data['status'],
            'resolution_notes' => $data['resolution_notes'],
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);

        RoleNotification::create([
            'user_id' => $privacyRequest->user_id,
            'role_scope' => 'customer',
            'type' => 'privacy.request_resolved',
            'title' => 'Privacy Request Updated',
            'body' => "Your {$privacyRequest->request_type} request has been marked {$privacyRequest->status}.",
            'action_url' => '/account#privacy-governance',
        ]);

        $this->audit->record('privacy.request.resolved', $privacyRequest, null, [
            'status' => $privacyRequest->status,
        ], $admin);

        return $privacyRequest->fresh();
    }
}
