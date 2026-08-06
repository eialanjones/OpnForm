<?php

namespace App\Http\Controllers\Forms\AI;

use App\Exceptions\UploadSecurityException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AI\StoreFormAiDocumentRequest;
use App\Http\Resources\FormAiDocumentResource;
use App\Models\Forms\AI\FormAiDocument;
use App\Models\Workspace;
use App\Service\Storage\FileUploadPathService;
use App\Service\Storage\StorageFileNameParser;
use App\Service\Storage\UploadSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Knowledge sources and structure templates for AI PDF blocks.
 *
 * Scoped by workspace + block id rather than by form, because a block can be
 * configured before the form it lives on has ever been saved.
 */
class FormAiDocumentController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'workspace_id' => ['required', 'integer'],
            'block_id' => ['required', 'uuid'],
        ]);

        $workspace = Workspace::find($validated['workspace_id']);
        if (!$workspace || !$request->user()->ownsWorkspace($workspace)) {
            return $this->error(['message' => 'You do not have access to this workspace.'], 403);
        }

        $documents = FormAiDocument::query()
            ->forBlock($workspace->id, $validated['block_id'])
            ->orderBy('id')
            ->get();

        return $this->success([
            'documents' => FormAiDocumentResource::collection($documents),
        ]);
    }

    public function store(StoreFormAiDocumentRequest $request, UploadSecurityService $uploadSecurityService)
    {
        $file = $request->file('file');
        $role = $request->input('role');
        $blockId = $request->input('block_id');
        // Authorised in StoreFormAiDocumentRequest; every query below is scoped
        // to it so a guessed block id can never reach another tenant.
        $workspaceId = (int) $request->input('workspace_id');

        $this->guardAgainstDisallowedType($file->getClientOriginalName());

        if ($role === FormAiDocument::ROLE_KNOWLEDGE) {
            $this->guardAgainstTooManyKnowledgeSources($workspaceId, $blockId);
        }

        try {
            $inspection = $uploadSecurityService->inspectUploadedFile($file);
        } catch (UploadSecurityException $exception) {
            throw ValidationException::withMessages(['file' => [$exception->getMessage()]]);
        }

        $this->guardAgainstDisallowedMimeType($inspection->mimeType);

        $storedName = $this->buildStoredName($file->getClientOriginalName());
        $path = FileUploadPathService::getAiDocumentPath($workspaceId, $blockId, $storedName);

        Storage::putFileAs(
            FileUploadPathService::getAiDocumentPath($workspaceId, $blockId),
            $file,
            $storedName
        );

        // A block keeps exactly one structure template; replacing it should not
        // leave the previous file behind.
        if ($role === FormAiDocument::ROLE_TEMPLATE) {
            FormAiDocument::query()
                ->forBlock($workspaceId, $blockId)
                ->where('role', FormAiDocument::ROLE_TEMPLATE)
                ->get()
                ->each->delete();
        }

        $document = FormAiDocument::create([
            'workspace_id' => $workspaceId,
            'form_id' => $request->input('form_id') ? (int) $request->input('form_id') : null,
            'block_id' => $blockId,
            'role' => $role,
            'original_name' => Str::limit($file->getClientOriginalName(), 180, ''),
            'stored_name' => $storedName,
            'mime_type' => $inspection->mimeType,
            'size_bytes' => Storage::size($path),
        ]);

        return $this->success([
            'message' => 'File uploaded. We are reading it now.',
            'document' => new FormAiDocumentResource($document),
        ]);
    }

    public function destroy(Request $request, FormAiDocument $formAiDocument)
    {
        $workspace = Workspace::find($formAiDocument->workspace_id);
        if (!$workspace || !$request->user()->ownsWorkspace($workspace) || $workspace->isReadonlyUser($request->user())) {
            return $this->error(['message' => 'You do not have access to this file.'], 403);
        }

        $formAiDocument->delete();

        return $this->success(['message' => 'File removed.']);
    }

    private function guardAgainstDisallowedType(string $originalName): void
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, config('ai_pdf.documents.allowed_extensions'), true)) {
            throw ValidationException::withMessages([
                'file' => ['Send a PDF, spreadsheet, CSV or text file.'],
            ]);
        }
    }

    private function guardAgainstDisallowedMimeType(string $mimeType): void
    {
        if (!in_array($mimeType, config('ai_pdf.documents.allowed_mime_types'), true)) {
            throw ValidationException::withMessages([
                'file' => ['This file type is not allowed.'],
            ]);
        }
    }

    private function guardAgainstTooManyKnowledgeSources(int $workspaceId, string $blockId): void
    {
        $limit = (int) config('ai_pdf.documents.max_knowledge_per_block');

        $current = FormAiDocument::query()
            ->forBlock($workspaceId, $blockId)
            ->where('role', FormAiDocument::ROLE_KNOWLEDGE)
            ->count();

        if ($current >= $limit) {
            throw ValidationException::withMessages([
                'file' => ["You can attach up to {$limit} knowledge sources to this block."],
            ]);
        }
    }

    /**
     * Reuses the storage naming convention already used for submissions:
     * a sanitised readable stem plus a UUID, so two uploads of the same file
     * never collide.
     */
    private function buildStoredName(string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $stem = pathinfo($originalName, PATHINFO_FILENAME);

        $reference = $stem . '_' . Str::uuid() . '.' . $extension;
        $storedName = StorageFileNameParser::parse($reference)->getMovedFileName();

        if (!$storedName) {
            throw ValidationException::withMessages(['file' => ['Invalid file name.']]);
        }

        return $storedName;
    }
}
