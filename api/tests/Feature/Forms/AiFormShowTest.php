<?php

use App\Jobs\Form\GenerateAiForm;
use App\Models\Forms\AI\AiFormCompletion;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake([GenerateAiForm::class]);
});

it('does not tell the user the form is ready when the generation failed', function () {
    $completion = AiFormCompletion::create([
        'form_prompt' => 'Inscrição para mentoria',
        'ip' => '127.0.0.1',
    ]);
    $completion->update([
        'status' => AiFormCompletion::STATUS_FAILED,
        'error' => 'The model `o4-mini` has been deprecated',
    ]);

    $this->getJson(route('forms.ai.show', $completion->id))
        ->assertSuccessful()
        ->assertJsonPath('ai_form_completion.status', AiFormCompletion::STATUS_FAILED)
        ->assertJsonMissing(['message' => 'Your data is ready! Feel free to customize it to your needs before publishing.']);
});

it('tells the user the form is ready when the generation completed', function () {
    $completion = AiFormCompletion::create([
        'form_prompt' => 'Inscrição para mentoria',
        'ip' => '127.0.0.1',
    ]);
    $completion->update([
        'status' => AiFormCompletion::STATUS_COMPLETED,
        'result' => '{}',
    ]);

    $this->getJson(route('forms.ai.show', $completion->id))
        ->assertSuccessful()
        ->assertJsonPath('message', 'Your data is ready! Feel free to customize it to your needs before publishing.');
});
