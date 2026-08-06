<?php

namespace App\Http\Requests\AI;

use App\Models\Forms\AI\FormAiDocument;
use App\Models\Forms\Form;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFormAiDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        $workspace = Workspace::find($this->input('workspace_id'));
        if (!$workspace || !$user->ownsWorkspace($workspace) || $workspace->isReadonlyUser($user)) {
            return false;
        }

        $formId = $this->input('form_id');
        if ($formId) {
            $form = Form::find($formId);
            if (!$form || $form->workspace_id !== $workspace->id) {
                return false;
            }
        }

        return true;
    }

    public function rules(): array
    {
        $maxKilobytes = (int) ceil(((int) config('ai_pdf.documents.max_file_size')) / 1024);
        $extensions = implode(',', config('ai_pdf.documents.allowed_extensions'));

        return [
            'workspace_id' => ['required', 'integer'],
            'form_id' => ['nullable', 'integer'],
            'block_id' => ['required', 'uuid'],
            'role' => ['required', Rule::in([FormAiDocument::ROLE_KNOWLEDGE, FormAiDocument::ROLE_TEMPLATE])],
            'file' => ['required', 'file', 'max:' . $maxKilobytes, 'mimes:' . $extensions],
        ];
    }

    public function messages(): array
    {
        $megabytes = (int) round(((int) config('ai_pdf.documents.max_file_size')) / 1048576);

        return [
            'file.max' => "Each file must be {$megabytes}MB or smaller.",
            'file.mimes' => 'Send a PDF, spreadsheet, CSV or text file.',
        ];
    }
}
