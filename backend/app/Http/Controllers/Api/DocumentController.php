<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Audit\AuditLogger;
use App\Compliance\DeadlineGenerator;
use App\Documents\DocumentUploadService;
use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ConfirmDocumentRequest;
use App\Http\Requests\Api\StoreDocumentRequest;
use App\Models\Document;
use App\Vault\DocumentVault;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Document::query()->latest('id')->get(),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['data' => Document::query()->findOrFail($id)]);
    }

    /**
     * Upload a document: bytes are encrypted into the vault, a Document row is
     * created (pending, unconfirmed). No deadline yet — that needs confirmation.
     */
    public function store(StoreDocumentRequest $request, DocumentUploadService $uploads): JsonResponse
    {
        $file = $request->file('file');

        $document = $uploads->store((string) $file->get(), [
            ...$request->safe()->only(['document_type_id', 'entity_id', 'person_id', 'issue_date', 'expiry_date']),
            'mime_type' => $file->getClientMimeType(),
            'original_filename' => $file->getClientOriginalName(),
        ]);

        return response()->json(['data' => $document], 201);
    }

    /**
     * The mandatory human-confirm step (SPEC.md §8). A human verifies/corrects the
     * extracted data; only then is the document confirmed and a deadline generated.
     * No extracted date becomes a live deadline without this gate.
     */
    public function confirm(ConfirmDocumentRequest $request, string $id, DeadlineGenerator $generator): JsonResponse
    {
        $document = Document::query()->findOrFail($id);

        $document->fill($request->safe()->only([
            'document_type_id', 'entity_id', 'person_id', 'issue_date', 'expiry_date',
        ]));
        $document->confirmed = true;
        $document->confirmed_by = $request->user()->id;
        $document->confirmed_at = now();
        $document->status = DocumentStatus::Active;
        $document->save();

        $generator->generate($document);

        app(AuditLogger::class)->log('document.confirmed', $document);

        return response()->json(['data' => $document->load('deadlines.alerts')]);
    }

    /**
     * Stream the decrypted bytes for a document the caller's tenant owns.
     */
    public function download(string $id, DocumentVault $vault): Response
    {
        $document = Document::query()->findOrFail($id);

        abort_if($document->file_ref === null, 404);

        $contents = $vault->retrieve($document->file_ref);

        // Audit the access to a sensitive resource (SPEC.md §8/§10).
        app(AuditLogger::class)->log('document.downloaded', $document);

        return response($contents, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="document-'.$document->id.'"',
        ]);
    }
}
