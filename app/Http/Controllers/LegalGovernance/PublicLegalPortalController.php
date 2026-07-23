<?php

namespace App\Http\Controllers\LegalGovernance;

use App\Http\Controllers\Controller;
use App\LegalGovernance\Services\PublicLegalDocumentService;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PublicLegalPortalController extends Controller
{
    public function __construct(private PublicLegalDocumentService $documents) {}

    public function index(): View
    {
        return view('legal.index', ['documents' => $this->documents->publicIndex(), 'footerDocuments' => $this->documents->footerDocuments()]);
    }

    public function show(string $slug, ?string $version = null): View
    {
        $document = $this->documents->publicDocument($slug, $version);
        abort_unless($document, 404);

        return view('legal.show', ['document' => $document, 'footerDocuments' => $this->documents->footerDocuments()]);
    }

    public function artifact(string $slug, string $version, string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['pdf', 'markdown', 'plain_text'], true), 404);
        $document = $this->documents->publicDocument($slug);
        abort_unless($document && $document['public_id'] === $version, 404);
        $artifact = $document['artifacts']->firstWhere('artifact_type', $type);
        abort_unless($artifact, 404);
        $extension = match ($type) {
            'pdf' => 'pdf', 'markdown' => 'md', default => 'txt'
        };

        return Storage::disk($artifact->storage_disk)->download($artifact->storage_path, $document['slug'].'-'.$document['version'].'.'.$extension);
    }
}
