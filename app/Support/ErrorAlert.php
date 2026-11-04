<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * E-mails unhandled exceptions to ERROR_ALERT_EMAIL, at most once per distinct
 * error every 30 minutes, so a broken page cannot flood the mailbox.
 */
final class ErrorAlert
{
    public static function send(Throwable $e): void
    {
        $to = config('app.error_alert_email');

        if (! $to || app()->runningUnitTests()) {
            return;
        }

        $signature = 'error-alert:'.sha1(get_class($e).'|'.$e->getFile().'|'.$e->getLine());

        // Cache::add only succeeds for the first occurrence inside the window
        if (! Cache::add($signature, true, now()->addMinutes(30))) {
            return;
        }

        try {
            $request = app()->runningInConsole() ? null : request();

            $body = implode("\n", array_filter([
                'Application: '.config('app.name').' ('.app()->environment().')',
                'Time: '.now()->toDateTimeString(),
                $request ? 'Request: '.$request->method().' '.$request->fullUrl() : 'Context: console',
                $request?->user() ? 'User ID: '.$request->user()->id : null,
                '',
                get_class($e).': '.$e->getMessage(),
                'at '.$e->getFile().':'.$e->getLine(),
                '',
                mb_substr($e->getTraceAsString(), 0, 6000),
            ], fn ($line) => $line !== null));

            Mail::raw($body, function ($message) use ($to, $e) {
                $message->to($to)->subject('['.config('app.name').'] Error: '.mb_substr(class_basename($e).' '.$e->getMessage(), 0, 120));
            });
        } catch (Throwable) {
            // Never let alerting break error handling
        }
    }
}
