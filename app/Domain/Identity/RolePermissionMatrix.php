<?php

namespace App\Domain\Identity;

final class RolePermissionMatrix
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_COACH = 'coach';

    public const ROLE_CUSTOMER = 'customer';

    /**
     * Canonical list of platform permissions.
     *
     * @return list<string>
     */
    public static function permissions(): array
    {
        return [
            'users.view',
            'users.manage',
            'staff.manage',
            'roles.manage',
            'settings.manage',
            'integrations.manage',
            'catalog.manage',
            'pricing.manage',
            'inventory.view',
            'inventory.adjust',
            'inventory.receive',
            'orders.view',
            'orders.fulfil',
            'orders.cancel',
            'payments.verify',
            'refunds.approve',
            'coupons.manage',
            'coaching.packages.manage',
            'coaching.subscriptions.manage',
            'coaching.deliver',
            'health.view',
            'diet.plans.manage',
            'diet.requests.handle',
            'gym.plans.manage',
            'gym.memberships.manage',
            'gym.checkins.record',
            'gym.classes.manage',
            'enquiries.manage',
            'support.handle',
            'reviews.moderate',
            'testimonials.manage',
            'content.manage',
            'seo.manage',
            'media.manage',
            'notifications.manage',
            'reports.operational',
            'reports.financial',
            'audit.view',
            'system.health',
        ];
    }

    /**
     * Role -> granted permissions map.
     * Note: Manager and Coach permissions with assignment or amount limits are
     * further constrained in Policies and Services.
     *
     * @return array<string, list<string>>
     */
    public static function roleMap(): array
    {
        return [
            self::ROLE_ADMIN => self::permissions(),

            self::ROLE_MANAGER => [
                'users.view',
                'users.manage', // customers only (enforced in UserPolicy)
                'catalog.manage',
                'inventory.view',
                'inventory.adjust',
                'inventory.receive',
                'orders.view',
                'orders.fulfil',
                'orders.cancel',
                'payments.verify',
                'coupons.manage',
                'coaching.subscriptions.manage',
                'coaching.deliver', // assigned clients only
                'health.view', // assigned clients only, audited
                'diet.requests.handle', // assigned only
                'gym.memberships.manage',
                'gym.checkins.record',
                'gym.classes.manage',
                'enquiries.manage',
                'support.handle',
                'reviews.moderate',
                'reports.operational',
            ],

            self::ROLE_COACH => [
                'users.view', // assigned clients only
                'coaching.deliver', // assigned clients only
                'health.view', // assigned clients only, audited
                'diet.requests.handle', // assigned & within qualifications
                'gym.classes.manage', // own classes only
                'support.handle', // assigned tickets only
            ],

            self::ROLE_CUSTOMER => [],
        ];
    }
}
