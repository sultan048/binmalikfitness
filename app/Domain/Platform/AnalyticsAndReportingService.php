<?php

namespace App\Domain\Platform;

use App\Domain\Identity\RolePermissionMatrix;
use App\Models\AuditLog;
use App\Models\Cart;
use App\Models\CoachingSubscription;
use App\Models\Entitlement;
use App\Models\GymCheckIn;
use App\Models\GymMembership;
use App\Models\MediaAsset;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PrivateFile;
use App\Models\ProductVariant;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AnalyticsAndReportingService
{
    public const VERIFIED_PAYMENT_STATUSES = [
        Order::PAYMENT_VERIFIED,
        Order::PAYMENT_PARTIALLY_REFUNDED,
        Order::PAYMENT_REFUNDED,
    ];

    public const BUSINESS_LINES = ['shop', 'coaching', 'diet', 'gym'];

    /**
     * Reconciled revenue analytics calculated strictly from orders with verified payments.
     * Unpaid orders, pending carts, and unverified bank proofs are NEVER counted as revenue.
     *
     * @return array<string, mixed>
     */
    public function getReconciledFinancialSummary(CarbonInterface $from, CarbonInterface $to): array
    {
        $verifiedOrders = Order::query()
            ->whereIn('payment_status', self::VERIFIED_PAYMENT_STATUSES)
            ->whereBetween('placed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get();

        $grossPaisa = (int) $verifiedOrders->sum('total_paisa');
        $refundedPaisa = (int) $verifiedOrders->sum('refunded_paisa');
        $netPaisa = max(0, $grossPaisa - $refundedPaisa);

        $byLine = [];
        foreach (self::BUSINESS_LINES as $line) {
            $lineOrders = $verifiedOrders->where('business_line', $line);
            $lineGross = (int) $lineOrders->sum('total_paisa');
            $lineRefunded = (int) $lineOrders->sum('refunded_paisa');
            $lineNet = max(0, $lineGross - $lineRefunded);

            $byLine[$line] = [
                'orders_count' => $lineOrders->count(),
                'gross_paisa' => $lineGross,
                'refunded_paisa' => $lineRefunded,
                'net_paisa' => $lineNet,
                'net_formatted' => Money::format($lineNet),
            ];
        }

        // Explicitly separate unverified/pending orders so staff can see pipeline without inflating revenue
        $unverifiedOrders = Order::query()
            ->whereIn('payment_status', [
                Order::PAYMENT_UNPAID,
                Order::PAYMENT_COD_PENDING,
                Order::PAYMENT_PROOF_SUBMITTED,
            ])
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->whereBetween('placed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'verified_orders_count' => $verifiedOrders->count(),
            'gross_verified_paisa' => $grossPaisa,
            'gross_verified_formatted' => Money::format($grossPaisa),
            'refunded_paisa' => $refundedPaisa,
            'refunded_formatted' => Money::format($refundedPaisa),
            'net_reconciled_paisa' => $netPaisa,
            'net_reconciled_formatted' => Money::format($netPaisa),
            'by_business_line' => $byLine,
            'unverified_pipeline_count' => $unverifiedOrders->count(),
            'unverified_pipeline_paisa' => (int) $unverifiedOrders->sum('total_paisa'),
            'unverified_pipeline_formatted' => Money::format((int) $unverifiedOrders->sum('total_paisa')),
            'active_entitlements_count' => Entitlement::where('status', Entitlement::STATUS_ACTIVE)->count(),
        ];
    }

    /**
     * Operational KPIs for Admin & Manager dashboards.
     *
     * @return array<string, mixed>
     */
    public function getOperationalMetrics(CarbonInterface $from, CarbonInterface $to): array
    {
        $lowStockVariants = ProductVariant::with('product')
            ->get()
            ->filter(fn (ProductVariant $v) => $v->availableStock() <= $v->low_stock_threshold)
            ->values();

        $expiringSubscriptions = CoachingSubscription::with(['user', 'package'])
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->addDays(14)])
            ->orderBy('ends_at')
            ->get();

        $expiringGymMemberships = GymMembership::with(['user', 'plan'])
            ->where('status', GymMembership::STATUS_ACTIVE)
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->addDays(14)])
            ->orderBy('ends_at')
            ->get();

        $activeCartsCount = Cart::whereHas('items')->count();
        $placedOrdersInRange = Order::whereBetween('placed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->count();
        $verifiedOrdersInRange = Order::whereIn('payment_status', self::VERIFIED_PAYMENT_STATUSES)
            ->whereBetween('placed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->count();

        return [
            'new_customers_count' => User::role(RolePermissionMatrix::ROLE_CUSTOMER)
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->count(),
            'total_customers_count' => User::role(RolePermissionMatrix::ROLE_CUSTOMER)->count(),
            'active_coaching_subscriptions_count' => CoachingSubscription::where('status', 'active')->count(),
            'active_gym_memberships_count' => GymMembership::where('status', GymMembership::STATUS_ACTIVE)->count(),
            'pending_orders_count' => Order::whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_AWAITING_PAYMENT,
                Order::STATUS_COD_CONFIRMED,
                Order::STATUS_PROCESSING,
            ])->count(),
            'pending_payment_proofs_count' => Payment::where('status', 'proof_submitted')->count(),
            'low_stock_variants' => $lowStockVariants,
            'low_stock_count' => $lowStockVariants->count(),
            'expiring_subscriptions' => $expiringSubscriptions,
            'expiring_gym_memberships' => $expiringGymMemberships,
            'open_support_tickets_count' => SupportTicket::whereIn('status', [
                SupportTicket::STATUS_OPEN,
                SupportTicket::STATUS_IN_PROGRESS,
            ])->count(),
            'today_gym_checkins_count' => GymCheckIn::whereDate('checked_in_at', today())->count(),
            'conversion_summary' => [
                'active_carts' => $activeCartsCount,
                'orders_placed_in_range' => $placedOrdersInRange,
                'orders_verified_in_range' => $verifiedOrdersInRange,
                'verification_rate_percent' => $placedOrdersInRange > 0
                    ? (int) round(($verifiedOrdersInRange / $placedOrdersInRange) * 100)
                    : 0,
            ],
        ];
    }

    /**
     * System & cPanel-readiness operational health monitor for the Admin dashboard.
     *
     * @return array<string, mixed>
     */
    public function getSystemHealthSummary(): array
    {
        $dbConnected = false;
        try {
            DB::connection()->getPdo();
            $dbConnected = true;
        } catch (\Throwable) {
            $dbConnected = false;
        }

        $privateDiskWritable = is_writable(storage_path('app/private')) || is_writable(storage_path('app'));
        $pendingJobsCount = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        $failedJobsCount = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

        return [
            'db_connected' => $dbConnected,
            'private_disk_writable' => $privateDiskWritable,
            'private_files_count' => PrivateFile::count(),
            'media_assets_count' => MediaAsset::count(),
            'pending_jobs_count' => $pendingJobsCount,
            'failed_jobs_count' => $failedJobsCount,
            'audit_logs_count' => AuditLog::count(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'environment' => app()->environment(),
            'queue_driver' => config('queue.default'),
            'session_driver' => config('session.driver'),
        ];
    }

    /**
     * Exports reconciled verified orders within the selected date range to CSV format.
     */
    public function exportVerifiedOrdersCsv(CarbonInterface $from, CarbonInterface $to): string
    {
        $orders = Order::with('user')
            ->whereIn('payment_status', self::VERIFIED_PAYMENT_STATUSES)
            ->whereBetween('placed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('placed_at')
            ->get();

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'Order Number',
            'Placed At',
            'Customer Name',
            'Customer Email',
            'Business Line',
            'Order Status',
            'Payment Method',
            'Payment Status',
            'Gross Amount (PKR)',
            'Refunded Amount (PKR)',
            'Net Reconciled Revenue (PKR)',
        ]);

        foreach ($orders as $order) {
            $grossPkr = number_format($order->total_paisa / 100, 2, '.', '');
            $refundedPkr = number_format($order->refunded_paisa / 100, 2, '.', '');
            $netPkr = number_format(max(0, $order->total_paisa - $order->refunded_paisa) / 100, 2, '.', '');

            fputcsv($handle, [
                $order->order_number,
                optional($order->placed_at)->toDateTimeString() ?? $order->created_at?->toDateTimeString(),
                $order->customer_name ?: ($order->user?->name ?? 'Guest'),
                $order->customer_email ?: ($order->user?->email ?? ''),
                strtoupper($order->business_line),
                $order->status,
                $order->payment_method,
                $order->payment_status,
                $grossPkr,
                $refundedPkr,
                $netPkr,
            ]);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
