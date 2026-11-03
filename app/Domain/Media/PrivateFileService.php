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
                'size_bytes' => (int) $file->getSize(),
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
}
