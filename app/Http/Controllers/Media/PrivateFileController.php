<?php

namespace App\Http\Controllers\Media;

use App\Domain\Platform\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\PrivateFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateFileController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function download(Request $request, PrivateFile $privateFile): StreamedResponse
    {
        Gate::authorize('view', $privateFile);

        if (! Storage::disk($privateFile->disk)->exists($privateFile->path)) {
            abort(404, 'File not found in private storage.');
        }

        $this->audit->record(
            action: 'media.private_file.accessed',
            subject: $privateFile,
            oldValues: null,
            newValues: [
                'owner_id' => $privateFile->owner_id,
                'category' => $privateFile->category,
            ],
            actor: $request->user(),
        );

        return Storage::disk($privateFile->disk)->response(
            $privateFile->path,
            $privateFile->original_name,
            [
                'Content-Type' => $privateFile->mime_type,
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
