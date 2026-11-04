<?php

namespace App\Domain\Media;

use App\Domain\Platform\AuditLogger;
use App\Models\ConsentRecord;
use App\Models\PrivateFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PrivateFileService
{
    /**
     * Allowed safe extensions & MIME types for private uploads.
     *
     * @var array<string, list<string>>
     */
    public const ALLOWED_TYPES = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'pdf' => ['application/pdf'],
    ];

    /**
     * Hard upper limit enforced here, regardless of controller validation.
     */
    public const MAX_BYTES = 10 * 1024 * 1024;

    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function store(
        User $uploader,
        User $owner,
        UploadedFile $file,
        string $category,
        bool $isConfidentialHealth = true,
        ?string $consentType = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): PrivateFile {
        $ext = strtolower($file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType());

        if (! isset(self::ALLOWED_TYPES[$ext]) || ! in_array($mime, self::ALLOWED_TYPES[$ext], true)) {
            throw new InvalidArgumentException('Unsupported or unsafe file type.');
        }

        if (! $file->isValid() || (int) $file->getSize() <= 0 || (int) $file->getSize() > self::MAX_BYTES) {
            throw new InvalidArgumentException('File is empty or larger than '.(self::MAX_BYTES / 1024 / 1024).' MB.');
        }

        // Images are decoded and re-encoded: strips EXIF/GPS metadata and anything appended to the pixels
        if (str_starts_with($mime, 'image/')) {
            $this->reencodeImage($file->getRealPath(), $mime);
        }

        return DB::transaction(function () use ($uploader, $owner, $file, $category, $isConfidentialHealth, $consentType, $ipAddress, $userAgent, $ext, $mime) {
            $consent = null;
            if ($consentType) {
                $consent = ConsentRecord::create([
                    'user_id' => $owner->id,
                    'type' => $consentType,
                    'granted_at' => now(),
                    'evidence_note' => "Explicit upload consent for {$category}",
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent ? substr($userAgent, 0, 255) : null,
                ]);
            }

            $storedFileName = Str::uuid()->toString().'.'.$ext;
            $directory = "private-uploads/user-{$owner->id}/{$category}";
            $storedPath = $file->storeAs($directory, $storedFileName, 'local');

            $fullPath = Storage::disk('local')->path($storedPath);
            $sha256 = is_file($fullPath) ? hash_file('sha256', $fullPath) : null;

            $record = PrivateFile::create([
                'owner_id' => $owner->id,
                'uploaded_by' => $uploader->id,
                'category' => $category,
                'original_name' => basename($file->getClientOriginalName()),
                'disk' => 'local',
                'path' => $storedPath,
                'mime_type' => $mime,
                'size_bytes' => (int) (filesize($fullPath) ?: $file->getSize()),
                'sha256_hash' => $sha256,
                'consent_record_id' => $consent?->id,
                'is_confidential_health' => $isConfidentialHealth,
            ]);

            $this->audit->record(
                action: 'media.private_file.uploaded',
                subject: $record,
                oldValues: null,
                newValues: [
                    'owner_id' => $owner->id,
                    'category' => $category,
                    'mime_type' => $mime,
                    'size_bytes' => $record->size_bytes,
                ],
                actor: $uploader,
            );

            return $record;
        });
    }

    /**
     * Re-encodes an uploaded image in place with GD. Rejects files GD cannot decode as an image.
     * Skipped (file kept as uploaded) only when GD or the format's codec is unavailable on the server.
     */
    private function reencodeImage(string $path, string $mime): void
    {
        $codecs = [
            'image/jpeg' => ['imagecreatefromjpeg', fn ($img) => imagejpeg($img, $path, 88)],
            'image/png' => ['imagecreatefrompng', fn ($img) => imagepng($img, $path, 6)],
            'image/webp' => ['imagecreatefromwebp', fn ($img) => imagewebp($img, $path, 88)],
        ];

        if (! isset($codecs[$mime]) || ! function_exists($codecs[$mime][0])) {
            return;
        }

        [$decode, $encode] = $codecs[$mime];

        $info = @getimagesize($path);
        if (! $info || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 40_000_000) {
            throw new InvalidArgumentException('The image could not be read or is too large.');
        }

        $image = @$decode($path);
        if (! $image) {
            throw new InvalidArgumentException('The image could not be read.');
        }

        if ($mime !== 'image/jpeg') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        $ok = $encode($image);
        imagedestroy($image);

        if (! $ok) {
            throw new InvalidArgumentException('The image could not be processed.');
        }

        clearstatcache(true, $path);
    }
}
