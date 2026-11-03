<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PreventDuplicateSubmission
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $key = (string) ($request->input('_idempotency_key') ?: $request->header('X-Idempotency-Key', ''));

        if ($key === '') {
            return $next($request);
        }

        $scopedKey = substr(($request->user()?->id ?? 'guest').':'.$key, 0, 100);

        try {
            DB::table('idempotency_keys')->insert([
                'key' => $scopedKey,
                'user_id' => $request->user()?->id,
                'method' => $request->method(),
                'path' => substr($request->path(), 0, 190),
                'created_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            abort(409, 'Duplicate submission detected. This request has already been processed.');
        }

        return $next($request);
    }
}
