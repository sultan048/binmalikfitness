<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Guest order access lives in the session instead of a ?token= URL parameter, so the
 * secret never ends up in browser history, server logs or Referer headers.
 * Access is granted when the order is placed, or after a successful order-track lookup
 * (order number + e-mail/phone). The legacy token is still accepted once and then dropped.
 */
final class OrderAccess
{
    private const SESSION_KEY = 'order_access';

    private const MAX_ORDERS = 20;

    public static function grant(Request $request, Order $order): void
    {
        $granted = array_values(array_diff((array) $request->session()->get(self::SESSION_KEY, []), [$order->order_number]));
        $granted[] = $order->order_number;

        $request->session()->put(self::SESSION_KEY, array_slice($granted, -self::MAX_ORDERS));
    }

    public static function has(Request $request, Order $order): bool
    {
        return in_array($order->order_number, (array) $request->session()->get(self::SESSION_KEY, []), true);
    }

    public static function tokenMatches(Request $request, Order $order): bool
    {
        $token = (string) ($request->query('token') ?? $request->input('token', ''));

        return $token !== '' && $order->access_token && hash_equals($order->access_token, $token);
    }
}
