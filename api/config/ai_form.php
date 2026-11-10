<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Model used by the AI form generator (whole form and extra fields)
    |--------------------------------------------------------------------------
    |
    | Kept in config so a model retired by OpenAI can be swapped with an env
    | change instead of a deploy. o4-mini was retired in Nov 2026 and broke
    | the generator for everyone.
    |
    */
    'model' => env('AI_FORM_MODEL', 'gpt-5.6-luna'),
];
