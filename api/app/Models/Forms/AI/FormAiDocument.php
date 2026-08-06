<?php

namespace App\Models\Forms\AI;

use App\Jobs\Form\ExtractFormAiDocumentText;
use App\Models\Forms\Form;
use App\Models\Workspace;
use App\Service\Storage\FileUploadPathService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A knowledge source or structure template attached to an AI PDF block.
 *
 * The text is extracted once, when the file is uploaded, so that answering the
 * form never pays the cost of parsing a large document again.
 */
class FormAiDocument extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const ROLE_KNOWLEDGE = 'knowledge';
    public const ROLE_TEMPLATE = 'template';

    protected $table = 'form_ai_documents';

    protected $fillable = [
        'workspace_id',
        'form_id',
        'block_id',
        'role',
        'original_name',
        'stored_name',
        'mime_type',
        'size_bytes',
        'status',
        'extracted_text',
        'extracted_characters',
        'error',
    ];

    /**
     * The extracted text can be hundreds of kilobytes and is only ever needed
     * server side while building the prompt. Never ship it to a client.
     */
    protected $hidden = [
        'extracted_text',
        'stored_name',
        'workspace_id',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'role' => self::ROLE_KNOWLEDGE,
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'extracted_characters' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $document) {
            ExtractFormAiDocumentText::dispatch($document);
        });

        static::deleted(function (self $document) {
            $path = $document->storagePath();
            if (Storage::exists($path)) {
                Storage::delete($path);
            }
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function storagePath(): string
    {
        return FileUploadPathService::getAiDocumentPath($this->workspace_id, $this->block_id, $this->stored_name);
    }

    /**
     * Documents are addressed by workspace *and* block id.
     *
     * A block id travels in the public form payload, so scoping a lookup by
     * block id alone would let any authenticated user reach into — or plant
     * documents inside — another workspace's block.
     */
    public function scopeForBlock($query, int|string $workspaceId, string $blockId)
    {
        return $query->where('workspace_id', $workspaceId)->where('block_id', $blockId);
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
