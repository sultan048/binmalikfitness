<?php

namespace Tests\Unit;

use App\Domain\Identity\RolePermissionMatrix;
use App\Domain\Identity\TwoFactorTotpService;
use App\Support\Money;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_money_converts_rupees_to_paisa_and_formats_pkr(): void
    {
        $paisa = Money::fromRupees(24500);

        $this->assertSame(2450000, $paisa);
        $this->assertSame('PKR 24,500', Money::formatPaisa($paisa));
        $this->assertSame('24,500', Money::formatPaisa($paisa, false));
        $this->assertSame('', Money::formatPaisa(null));
    }

    public function test_manager_role_excludes_owner_and_sensitive_permissions(): void
    {
        $map = RolePermissionMatrix::roleMap();
        $adminPerms = $map[RolePermissionMatrix::ROLE_ADMIN];
        $managerPerms = $map[RolePermissionMatrix::ROLE_MANAGER];
        $coachPerms = $map[RolePermissionMatrix::ROLE_COACH];
        $customerPerms = $map[RolePermissionMatrix::ROLE_CUSTOMER];

        $restrictedFromManager = [
            'staff.manage',
            'roles.manage',
            'settings.manage',
            'integrations.manage',
            'pricing.manage',
            'refunds.approve',
            'coaching.packages.manage',
            'diet.plans.manage',
            'gym.plans.manage',
            'reports.financial',
            'audit.view',
            'system.health',
        ];

        foreach ($restrictedFromManager as $restricted) {
            $this->assertContains($restricted, $adminPerms);
            $this->assertNotContains($restricted, $managerPerms, "Manager must not hold {$restricted}");
            $this->assertNotContains($restricted, $coachPerms, "Coach must not hold {$restricted}");
        }

        $this->assertSame([], $customerPerms);
    }

    public function test_two_factor_totp_service_generates_and_verifies_rfc6238_codes(): void
    {
        $totp = new TwoFactorTotpService;
        $secret = $totp->generateSecret();
        $fixedTime = 1792956000;

        $this->assertSame(32, strlen($secret));

        $code = $totp->currentCode($secret, $fixedTime);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertTrue($totp->verifyCode($secret, $code, 1, $fixedTime));
        $this->assertFalse($totp->verifyCode($secret, '000000', 0, $fixedTime));

        $recovery = $totp->generateRecoveryCodes();
        $this->assertCount(8, $recovery);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $recovery[0]);
    }
}
