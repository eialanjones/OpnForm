<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the editor needs to render a knowledge source row. Deliberately excludes
 * the extracted text and the storage name.
 */
class FormAiDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'block_id' => $this->block_id,
            'role' => $this->role,
            'original_name' => $this->original_name,
            'size_bytes' => (int) $this->size_bytes,
            'status' => $this->status,
            'extracted_characters' => $this->extracted_characters,
            'error' => $this->error,
            'created_at' => $this->created_at,
        ];
    }
}
