<?php

namespace App\Support\Compat;

/**
 * Magic-byte detection behind the fileinfo fallback (bootstrap/fileinfo-fallback.php), used on hosts
 * without ext-fileinfo. It only recognises the formats the app accepts; everything else is reported as
 * plain text or octet-stream, so an unrecognised file can never pass an allow-list check.
 */
final class MimeSniffer
{
    public const SAMPLE_BYTES = 8192;

    private const FLAG_MIME_TYPE = 16;

    private const FLAG_MIME_ENCODING = 1024;

    public static function type(string $bytes): string
    {
        return match (true) {
            $bytes === '' => 'application/x-empty',
            str_starts_with($bytes, "\xFF\xD8\xFF") => 'image/jpeg',
            str_starts_with($bytes, "\x89PNG\r\n\x1A\n") => 'image/png',
            str_starts_with($bytes, 'GIF87a'), str_starts_with($bytes, 'GIF89a') => 'image/gif',
            str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP' => 'image/webp',
            str_starts_with($bytes, '%PDF-') => 'application/pdf',
            str_starts_with($bytes, "PK\x03\x04") => 'application/zip',
            self::isText($bytes) => 'text/plain',
            default => 'application/octet-stream',
        };
    }

    public static function encoding(string $bytes): string
    {
        if ($bytes === '' || ! self::isText($bytes)) {
            return 'binary';
        }

        return preg_match('/[\x80-\xFF]/', $bytes) === 1 ? 'utf-8' : 'us-ascii';
    }

    /**
     * Formats the result the way finfo does for the given FILEINFO_* flags.
     */
    public static function describe(string $bytes, int $flags): string
    {
        $type = self::type($bytes);
        $wantsType = ($flags & self::FLAG_MIME_TYPE) !== 0;
        $wantsEncoding = ($flags & self::FLAG_MIME_ENCODING) !== 0;

        return match (true) {
            $wantsType && $wantsEncoding => $type.'; charset='.self::encoding($bytes),
            $wantsEncoding => self::encoding($bytes),
            default => $type,
        };
    }

    private static function isText(string $bytes): bool
    {
        return preg_match('/[\x00-\x08\x0E-\x1A\x1C-\x1F]/', $bytes) === 0;
    }
}
