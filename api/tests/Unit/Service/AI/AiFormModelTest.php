<?php

use App\Service\AI\Prompts\Form\GenerateFormFieldsPrompt;
use App\Service\AI\Prompts\Form\GenerateFormPrompt;

uses(\Tests\TestCase::class);

function aiFormPromptModel(object $prompt): string
{
    $property = new ReflectionProperty($prompt, 'model');
    $property->setAccessible(true);

    return $property->getValue($prompt);
}

it('uses the configured model for the AI form generator', function () {
    config(['ai_form.model' => 'gpt-test-model', 'services.openai.api_key' => 'test-key']);

    expect(aiFormPromptModel(new GenerateFormPrompt('Inscrição')))->toBe('gpt-test-model')
        ->and(aiFormPromptModel(new GenerateFormFieldsPrompt('Telefone')))->toBe('gpt-test-model');
});

it('no longer defaults to the retired o4-mini model', function () {
    expect(config('ai_form.model'))->not->toBe('o4-mini');
});
