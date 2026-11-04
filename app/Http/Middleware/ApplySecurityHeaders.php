<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class ApplySecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        // HSTS only over HTTPS in production, so local http:// development is never pinned to HTTPS
        if ($request->isSecure() && app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($this->shouldBlockSearchIndexing($request)) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }

    /**
     * Scripts come only from this origin: Alpine runs on its CSP build, so neither
     * 'unsafe-inline' nor 'unsafe-eval' is needed. Inline styles stay allowed
     * (style attributes in templates; they cannot execute code).
     */
    private function contentSecurityPolicy(): string
    {
        $scriptSrc = ["'self'"];
        $connectSrc = ["'self'"];
        $styleSrc = ["'self'", "'unsafe-inline'"];

        // `npm run dev` serves assets from the Vite dev server
        if (Vite::isRunningHot()) {
            $hot = rtrim((string) file_get_contents(public_path('hot')));
            $scriptSrc[] = $hot;
            $styleSrc[] = $hot;
            $connectSrc[] = $hot;
            $connectSrc[] = preg_replace('#^http#', 'ws', $hot);
        }

        return implode('; ', [
            "default-src 'self'",
            'script-src '.implode(' ', $scriptSrc),
            'style-src '.implode(' ', $styleSrc),
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            'connect-src '.implode(' ', $connectSrc),
            "object-src 'none'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
    }

    private function shouldBlockSearchIndexing(Request $request): bool
    {
        return $request->is(
            'manage',
            'manage/*',
            'account',
            'account/*',
            'cart',
            'cart/*',
            'checkout',
            'orders/*',
            'private-files/*',
            'login',
            'register',
            'demo-login',
            'forgot-password',
            'reset-password/*',
            'two-factor*',
            'email/*'
        );
    }
}
